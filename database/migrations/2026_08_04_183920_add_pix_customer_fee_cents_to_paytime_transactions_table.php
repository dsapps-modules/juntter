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
        Schema::table('paytime_transactions', function (Blueprint $table) {
            $table->integer('pix_customer_fee_cents')->default(0)->after('fees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paytime_transactions', function (Blueprint $table) {
            $table->dropColumn('pix_customer_fee_cents');
        });
    }
};
