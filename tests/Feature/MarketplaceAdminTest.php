<?php

namespace Tests\Feature;

use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceShippingProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketplaceAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_marketplace_category_and_flat_print_product(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post(route('admin.marketplace.categories.store'), [
            'name' => 'Wydruki soczewkowe',
            'description' => 'Druk UV na folii soczewkowej.',
            'is_active' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $category = MarketplaceCategory::query()->sole();
        $this->assertSame('Wydruki soczewkowe', $category->sourceTranslation()?->name);
        $this->assertSame('source', $category->sourceTranslation()?->translation_status->value);
        $response = $this->actingAs($admin)->post(route('admin.marketplace.products.store'), [
            'marketplace_category_id' => $category->id,
            'name' => 'Wydruk UV 3D A3',
            'short_description' => 'Płaski wydruk lentikularny.',
            'description' => 'Gotowy wydruk projektu klienta w maksymalnym formacie A3.',
            'image' => UploadedFile::fake()->image('print.jpg'),
            'print_size' => 'A3',
            'token_cost' => 60,
            'is_active' => '1',
        ]);

        $product = MarketplaceProduct::query()->sole();
        $response->assertRedirect(route('admin.marketplace.products.edit', $product))->assertSessionHasNoErrors();
        $this->assertSame(60, $product->token_cost);
        Storage::disk('public')->assertExists($product->image_path);
        $this->actingAs($admin)->get(route('admin.marketplace.products.index'))->assertOk()->assertSee('Wydruk UV 3D A3');
        $this->actingAs($admin)->get(route('admin.marketplace.products.edit', $product))->assertOk()->assertSee('Płaski wydruk lentikularny.');
        $this->actingAs($admin)->get(route('admin.marketplace.categories.index'))
            ->assertOk()
            ->assertSee('Wydruki soczewkowe')
            ->assertSee('Utworzone kategorie')
            ->assertSee('Tłumaczenie AI')
            ->assertSee('<table', false)
            ->assertDontSee('value="Wydruki soczewkowe"', false);
    }

    public function test_admin_can_edit_polish_and_english_marketplace_category_versions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $category = MarketplaceCategory::query()->create([
            'name' => 'Wydruki',
            'slug' => 'wydruki',
            'source_locale' => 'pl',
        ]);
        $category->translations()->create([
            'locale' => 'pl',
            'name' => 'Wydruki',
            'slug' => 'wydruki',
            'description' => 'Opis PL',
            'translation_status' => 'source',
        ]);

        $this->actingAs($admin)->get(route('admin.marketplace.categories.edit', $category))
            ->assertOk()
            ->assertSee('PL — Polski')
            ->assertSee('EN — English');

        $this->actingAs($admin)->put(route('admin.marketplace.categories.update', $category), [
            'source_locale' => 'pl',
            'sort_order' => 10,
            'is_active' => '1',
            'translations' => [
                'pl' => ['name' => 'Wydruki', 'slug' => 'wydruki', 'description' => 'Opis PL', 'translation_status' => 'draft'],
                'en' => ['name' => 'Prints', 'slug' => 'prints', 'description' => 'English description', 'translation_status' => 'ready'],
            ],
        ])->assertRedirect(route('admin.marketplace.categories.index'))->assertSessionHasNoErrors();

        $category->refresh();
        $this->assertSame('source', $category->translation('pl')?->translation_status->value);
        $this->assertSame('Prints', $category->translation('en')?->name);
        $this->assertSame('ready', $category->translation('en')?->translation_status->value);
    }

    public function test_product_size_larger_than_a3_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $category = MarketplaceCategory::query()->create(['name' => 'Druk', 'slug' => 'druk']);

        $this->actingAs($admin)->from(route('admin.marketplace.products.create'))->post(route('admin.marketplace.products.store'), [
            'marketplace_category_id' => $category->id,
            'name' => 'Za duży wydruk',
            'short_description' => 'Opis',
            'description' => 'Opis produktu',
            'print_size' => 'A2',
            'token_cost' => 100,
        ])->assertRedirect(route('admin.marketplace.products.create'))->assertSessionHasErrors('print_size');

        $this->assertDatabaseCount('marketplace_products', 0);
    }

    public function test_admin_can_define_poland_foreign_and_a3_shipping_rates(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post(route('admin.marketplace.providers.store'), [
            'name' => 'Furgonetka',
            'is_active' => '1',
            'rates' => [
                ['country_code' => 'PL', 'print_size' => '', 'token_cost' => 8],
                ['country_code' => 'PL', 'print_size' => 'A3', 'token_cost' => 12],
                ['country_code' => '', 'print_size' => '', 'token_cost' => 30],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $provider = MarketplaceShippingProvider::query()->with('rates')->sole();
        $this->assertCount(3, $provider->rates);
        $this->assertSame(12, $provider->rates->firstWhere('print_size', 'A3')->token_cost);
        $this->assertSame(12, $provider->tokenCostFor('PL', 'A3'));
        $this->assertSame(8, $provider->tokenCostFor('PL', 'A4'));
        $this->assertSame(30, $provider->tokenCostFor('DE', 'A4'));

        $this->actingAs($admin)->get(route('admin.marketplace.providers.index'))->assertOk()->assertSee('Furgonetka');
        $this->actingAs($admin)->get(route('admin.marketplace.providers.edit', $provider))->assertOk()->assertSee('Furgonetka');
    }

    public function test_non_admin_cannot_access_marketplace_management(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.marketplace.products.index'))->assertForbidden();
    }
}
