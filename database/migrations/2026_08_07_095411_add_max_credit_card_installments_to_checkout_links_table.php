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
        if (! Schema::hasTable('checkout_links') || Schema::hasColumn('checkout_links', 'max_credit_card_installments')) {
            return;
        }

        Schema::table('checkout_links', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_credit_card_installments')->default(18)->after('allow_credit_card');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('checkout_links') || ! Schema::hasColumn('checkout_links', 'max_credit_card_installments')) {
            return;
        }

        Schema::table('checkout_links', function (Blueprint $table) {
            $table->dropColumn('max_credit_card_installments');
        });
    }
};
