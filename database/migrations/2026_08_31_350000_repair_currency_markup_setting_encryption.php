<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        $setting = DB::table('app_settings')
            ->where('group', 'currency')
            ->where('key', 'markup_percent')
            ->first();

        $plainValue = null;

        if ($setting && filled($setting->value)) {
            try {
                $plainValue = Crypt::decryptString(
                    (string) $setting->value
                );
            } catch (\Throwable) {
                $plainValue = trim(
                    (string) $setting->value
                );
            }
        }

        if (
            blank($plainValue)
            || in_array(
                trim((string) $plainValue),
                ['0', '0.0', '0.00'],
                true
            )
        ) {
            $plainValue = '5.00';
        }

        $encryptedValue = Crypt::encryptString(
            trim((string) $plainValue)
        );

        if (! $setting) {
            DB::table('app_settings')->insert([
                'group' => 'currency',
                'key' => 'markup_percent',
                'value' => $encryptedValue,
                'is_secret' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('app_settings')
            ->where('id', $setting->id)
            ->update([
                'value' => $encryptedValue,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No-op by design.
    }
};
