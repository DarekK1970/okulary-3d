<?php

namespace Tests\Feature;

use App\Enums\CatalogTranslationStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CartCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function productWithVariant(
        int $stock = 10,
        string $currency = 'PLN'
    ): array {
        $category = ProductCategory::create([
            'source_locale' => 'pl',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        ProductCategoryTranslation::create([
            'product_category_id' => $category->id,
            'locale' => 'pl',
            'name' => 'Okulary 3D',
            'slug' => 'okulary-3d',
            'translation_status' => CatalogTranslationStatus::Source,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'source_locale' => 'pl',
            'status' => ProductStatus::Active,
            'brand' => 'Elverre',
        ]);

        ProductTranslation::create([
            'product_id' => $product->id,
            'locale' => 'pl',
            'name' => 'Okulary czerwono-cyjanowe',
            'slug' => 'okulary-czerwono-cyjanowe',
            'short_description' => 'Produkt testowy.',
            'description_html' => '<p>Opis.</p>',
            'translation_status' => CatalogTranslationStatus::Source,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-' . strtoupper($currency),
            'name' => 'Standard',
            'price_gross' => 19.99,
            'vat_rate' => 23,
            'currency' => $currency,
            'stock_quantity' => $stock,
            'weight_grams' => 500,
            'track_stock' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return [$product, $variant];
    }

    private function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_email' => 'buyer@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
            'customer_phone' => '123456789',
            'billing_company' => '',
            'billing_tax_id' => '',
            'billing_address_line1' => 'ul. Testowa 1',
            'billing_address_line2' => '',
            'billing_postal_code' => '87-100',
            'billing_city' => 'Toruń',
            'billing_country_code' => 'PL',
            'shipping_same_as_billing' => '1',
            'shipping_first_name' => '',
            'shipping_last_name' => '',
            'shipping_company' => '',
            'shipping_address_line1' => '',
            'shipping_address_line2' => '',
            'shipping_postal_code' => '',
            'shipping_city' => '',
            'shipping_country_code' => 'PL',
            'shipping_method' => 'pickup',
            'shipping_point' => '',
            'payment_method' => 'bank_transfer',
            'customer_note' => 'Test zamówienia.',
            'accept_terms' => '1',
        ], $overrides);
    }

    public function test_guest_can_add_variant_to_cart(): void
    {
        [, $variant] = $this->productWithVariant();

        $this->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])
            ->assertRedirect('/pl/cart');

        $this->get('/pl/cart')
            ->assertOk()
            ->assertSee('Okulary czerwono-cyjanowe')
            ->assertSee('39,98');
    }

    public function test_checkout_creates_order_and_decrements_stock(): void
    {
        [, $variant] = $this->productWithVariant(stock: 10);

        $this->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response = $this->post(
            '/pl/checkout',
            $this->checkoutPayload()
        );

        $order = Order::query()->firstOrFail();

        $response->assertRedirect(
            '/pl/order/' . $order->public_token
        );

        $this->assertSame(
            OrderStatus::Pending,
            $order->status
        );

        $this->assertSame('39.98', $order->subtotal_gross);
        $this->assertSame('39.98', $order->total_gross);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'sku_snapshot' => $variant->sku,
            'quantity' => 2,
            'line_total_gross' => '39.98',
        ]);

        $this->assertSame(
            8,
            $variant->fresh()->stock_quantity
        );

        $this->get('/pl/cart')
            ->assertOk()
            ->assertSee('Koszyk jest pusty');
    }

    public function test_authenticated_order_appears_in_customer_history(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        [, $variant] = $this->productWithVariant();

        $this->actingAs($user)
            ->post('/pl/cart/items', [
                'variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        $this->actingAs($user)
            ->post(
                '/pl/checkout',
                $this->checkoutPayload([
                    'customer_email' => $user->email,
                ])
            )
            ->assertRedirect();

        $order = Order::query()->firstOrFail();

        $this->assertSame($user->id, $order->user_id);

        $this->actingAs($user)
            ->get('/pl/account/orders')
            ->assertOk()
            ->assertSee($order->number);
    }

    public function test_checkout_rejects_quantity_above_stock(): void
    {
        [, $variant] = $this->productWithVariant(stock: 1);

        $this->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_admin_can_cancel_order_and_stock_is_released_once(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        [, $variant] = $this->productWithVariant(stock: 10);

        $this->post('/pl/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 3,
        ]);

        $this->post(
            '/pl/checkout',
            $this->checkoutPayload()
        );

        $order = Order::query()->firstOrFail();

        $this->assertSame(7, $variant->fresh()->stock_quantity);

        $this->actingAs($admin)
            ->patch('/admin/orders/' . $order->id . '/status', [
                'status' => OrderStatus::Cancelled->value,
            ])
            ->assertSessionHasNoErrors();

        $order->refresh();

        $this->assertSame(
            OrderStatus::Cancelled,
            $order->status
        );

        $this->assertNotNull($order->stock_released_at);

        $this->assertSame(
            10,
            $variant->fresh()->stock_quantity
        );

        $this->actingAs($admin)
            ->patch('/admin/orders/' . $order->id . '/status', [
                'status' => OrderStatus::Processing->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(
            10,
            $variant->fresh()->stock_quantity
        );
    }

    public function test_editor_cannot_access_order_register(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
        ]);

        $this->actingAs($editor)
            ->get('/admin/orders')
            ->assertForbidden();
    }
}
