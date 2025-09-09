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
        Schema::table('links_pagamento', function (Blueprint $table) {
            $table->boolean('is_avista')->default(false)->after('parcelas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('links_pagamento', function (Blueprint $table) {
            $table->dropColumn('is_avista');
        });
    }
};
