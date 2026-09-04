<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('preferred_locale', 10)->default('pl')->after('lenticular_plan')->index();
            $table->timestamp('plan_expires_at')->nullable()->after('preferred_locale')->index();
            $table->timestamp('last_activity_at')->nullable()->after('plan_expires_at')->index();
            $table->timestamp('suspended_at')->nullable()->after('last_activity_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['preferred_locale']);
            $table->dropIndex(['plan_expires_at']);
            $table->dropIndex(['last_activity_at']);
            $table->dropIndex(['suspended_at']);
            $table->dropColumn(['preferred_locale', 'plan_expires_at', 'last_activity_at', 'suspended_at']);
        });
    }
};
