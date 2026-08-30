<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('product_categories', 'parent_id')) {
            return;
        }

        Schema::table('product_categories', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->index()
                ->constrained('product_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('product_categories', 'parent_id')) {
            return;
        }

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
