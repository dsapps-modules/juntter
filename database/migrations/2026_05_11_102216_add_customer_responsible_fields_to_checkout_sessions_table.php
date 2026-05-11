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
        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->string('customer_responsible_document')->nullable()->after('customer_company_name');
            $table->date('customer_responsible_birth_date')->nullable()->after('customer_responsible_document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'customer_responsible_document',
                'customer_responsible_birth_date',
            ]);
        });
    }
};
