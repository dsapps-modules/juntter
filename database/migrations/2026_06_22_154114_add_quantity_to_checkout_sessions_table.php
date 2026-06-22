<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('checkout_sessions') || Schema::hasColumn('checkout_sessions', 'quantity')) {
            return;
        }

        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('checkout_sessions') || ! Schema::hasColumn('checkout_sessions', 'quantity')) {
            return;
        }

        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
