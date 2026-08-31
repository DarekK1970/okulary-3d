<?php

namespace Tests\Feature;

use App\Services\CurrencySettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CurrencySettingEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_conversion_margin_is_encrypted_and_readable(): void
    {
        $raw = DB::table('app_settings')
            ->where('group', 'currency')
            ->where('key', 'markup_percent')
            ->value('value');

        $this->assertNotNull($raw);
        $this->assertNotSame('5.00', $raw);

        $this->assertSame(
            '5.00',
            Crypt::decryptString((string) $raw)
        );

        $this->assertSame(
            '5.00',
            app(CurrencySettingsService::class)
                ->markupPercent()
        );
    }

    public function test_setting_remains_encrypted_after_service_update(): void
    {
        $service = app(
            CurrencySettingsService::class
        );

        $service->set(
            'markup_percent',
            '2.75'
        );

        $service->flush();

        $this->assertSame(
            '2.75',
            $service->markupPercent()
        );

        $raw = DB::table('app_settings')
            ->where('group', 'currency')
            ->where('key', 'markup_percent')
            ->value('value');

        $this->assertNotSame('2.75', $raw);

        $this->assertSame(
            '2.75',
            Crypt::decryptString((string) $raw)
        );
    }
}
