<?php

namespace Tests\Feature;

use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Services\TokenLensWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_is_linked_from_lab_and_shop_menus_and_lists_active_products(): void
    {
        $category = MarketplaceCategory::query()->create(['name' => 'Wydruki soczewkowe', 'slug' => 'wydruki']);
        MarketplaceProduct::query()->create([
            'marketplace_category_id' => $category->id,
            'name' => 'Wydruk UV 3D A3',
            'slug' => 'wydruk-uv-a3',
            'short_description' => 'Profesjonalny wydruk projektu.',
            'description' => 'Pełny opis.',
            'print_size' => 'A3',
            'token_cost' => 60,
            'is_active' => true,
        ]);

        $this->get('/pl/marketplace')->assertOk()
            ->assertSee('Marketplace usług druku 3D')
            ->assertSee('Wydruk UV 3D A3')
            ->assertSee('60 TOKEN_LENS')
            ->assertSee('Marketplace (Usługi)')
            ->assertSee('Marketplace usług druku');
    }

    public function test_authenticated_header_shows_name_plan_balance_and_expiry(): void
    {
        $user = User::factory()->create(['name' => 'Darek', 'lenticular_plan' => 'premium']);
        app(TokenLensWalletService::class)->grant($user, 100, 'premium_subscription', 'subscription:test', now()->setDate(2027, 6, 10));

        $this->actingAs($user)->get('/pl')->assertOk()
            ->assertSee('Darek')
            ->assertSee('PREMIUM')
            ->assertSee('Twoje TOKEN_LENS: 100')
            ->assertSee('ważne do 10.06.2027');
    }

    public function test_inactive_products_are_hidden(): void
    {
        $category = MarketplaceCategory::query()->create(['name' => 'Druk', 'slug' => 'druk']);
        MarketplaceProduct::query()->create([
            'marketplace_category_id' => $category->id, 'name' => 'Ukryty produkt', 'slug' => 'ukryty',
            'short_description' => 'Opis', 'description' => 'Opis', 'print_size' => 'A4', 'token_cost' => 30, 'is_active' => false,
        ]);

        $this->get('/pl/marketplace')->assertOk()->assertDontSee('Ukryty produkt')->assertSee('Oferta jest przygotowywana');
    }
}
