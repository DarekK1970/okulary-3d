<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use App\Services\CommerceSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommerceSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_commerce_settings(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->actingAs($superAdmin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('Ustawienia sklepu i płatności')
            ->assertSee('Ustawienia PayNow');
    }

    public function test_admin_cannot_open_sensitive_commerce_settings(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_settings_are_saved_and_paynow_secrets_are_encrypted(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->actingAs($superAdmin)
            ->put('/admin/settings', [
                'paynow_enabled' => '1',
                'paynow_sandbox' => '1',
                'paynow_api_key' => 'secret-api-key-123',
                'paynow_signature_key' =>
                    'secret-signature-key-456',
                'paynow_timeout' => 15,

                'bank_recipient' => 'ELVERRE POLSKA',
                'bank_name' => 'Test Bank',
                'bank_account' => 'PL001122334455',
                'bank_swift' => 'TESTPLPW',

                'seller_name' => 'ELVERRE POLSKA sp. z o.o.',
                'seller_address' => 'Toruń',
                'seller_tax_id' => '1234567890',
                'seller_email' => 'shop@example.com',
            ])
            ->assertSessionHasNoErrors();

        $settings = app(CommerceSettingsService::class);

        $this->assertTrue($settings->payNowEnabled());
        $this->assertTrue($settings->payNowSandbox());
        $this->assertSame(
            'secret-api-key-123',
            $settings->payNowApiKey()
        );
        $this->assertSame(
            'secret-signature-key-456',
            $settings->payNowSignatureKey()
        );

        $rawApiKey = DB::table('app_settings')
            ->where('group', 'commerce')
            ->where('key', 'paynow.api_key')
            ->value('value');

        $rawSignatureKey = DB::table('app_settings')
            ->where('group', 'commerce')
            ->where('key', 'paynow.signature_key')
            ->value('value');

        $this->assertNotSame(
            'secret-api-key-123',
            $rawApiKey
        );
        $this->assertNotSame(
            'secret-signature-key-456',
            $rawSignatureKey
        );
        $this->assertStringNotContainsString(
            'secret-api-key-123',
            (string) $rawApiKey
        );
        $this->assertStringNotContainsString(
            'secret-signature-key-456',
            (string) $rawSignatureKey
        );

        $this->assertDatabaseHas('app_settings', [
            'group' => 'commerce',
            'key' => 'paynow.api_key',
            'is_secret' => 1,
        ]);
    }

    public function test_blank_secret_fields_keep_existing_keys(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $settings = app(CommerceSettingsService::class);
        $settings->set(
            'paynow.api_key',
            'existing-api-key',
            true
        );
        $settings->set(
            'paynow.signature_key',
            'existing-signature',
            true
        );

        $this->actingAs($superAdmin)
            ->put('/admin/settings', [
                'paynow_enabled' => '1',
                'paynow_sandbox' => '1',
                'paynow_api_key' => '',
                'paynow_signature_key' => '',
                'paynow_timeout' => 15,

                'bank_recipient' => '',
                'bank_name' => '',
                'bank_account' => '',
                'bank_swift' => '',

                'seller_name' => 'Wortal Okulary 3D',
                'seller_address' => '',
                'seller_tax_id' => '',
                'seller_email' => '',
            ])
            ->assertSessionHasNoErrors();

        $settings->flush();

        $this->assertSame(
            'existing-api-key',
            $settings->payNowApiKey()
        );
        $this->assertSame(
            'existing-signature',
            $settings->payNowSignatureKey()
        );
    }
}
