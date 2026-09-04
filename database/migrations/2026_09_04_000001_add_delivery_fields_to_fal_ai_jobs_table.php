<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fal_ai_jobs', function (Blueprint $table): void {
            $table->timestamp('result_claimed_at')->nullable()->index()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('fal_ai_jobs', function (Blueprint $table): void {
            $table->dropColumn('result_claimed_at');
        });
    }
};
