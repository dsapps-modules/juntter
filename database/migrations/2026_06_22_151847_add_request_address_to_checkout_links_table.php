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
        if (! Schema::hasTable('checkout_links') || Schema::hasColumn('checkout_links', 'request_address')) {
            return;
        }

        Schema::table('checkout_links', function (Blueprint $table) {
            $table->boolean('request_address')->default(true)->after('allow_credit_card');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('checkout_links') || ! Schema::hasColumn('checkout_links', 'request_address')) {
            return;
        }

        Schema::table('checkout_links', function (Blueprint $table) {
            $table->dropColumn('request_address');
        });
    }
};
