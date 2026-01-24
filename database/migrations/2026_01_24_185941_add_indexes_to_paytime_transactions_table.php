<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table('paytime_transactions', function (Blueprint $table) {
                $table->index('created_at');
            });
        } catch (\Exception $e) {
            // Provavelmente já existe
        }

        try {
            Schema::table('paytime_transactions', function (Blueprint $table) {
                $table->index('establishment_id');
            });
        } catch (\Exception $e) {
            // Provavelmente já existe
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paytime_transactions', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['establishment_id']);
        });
    }
};
