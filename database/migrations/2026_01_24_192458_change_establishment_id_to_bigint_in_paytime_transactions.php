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
        // 1. Garantir que não existam índices que bloqueiem a alteração (caso existam com nomes diferentes)
        try {
            Schema::table('paytime_transactions', function (Blueprint $table) {
                // Removemos o índice criado anteriormente para trocar o tipo da coluna com segurança
                $table->dropIndex(['establishment_id']);
            });
        } catch (\Exception $e) {
            // Se o índice não existir com esse nome, apenas prossegue
        }

        // 2. Alterar o tipo da coluna para BIGINT UNSIGNED para casar com paytime_establishments.id
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE paytime_transactions MODIFY establishment_id BIGINT(20) UNSIGNED');

        // 3. Re-adicionar o índice para performance
        Schema::table('paytime_transactions', function (Blueprint $table) {
            $table->index('establishment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paytime_transactions', function (Blueprint $table) {
            $table->dropIndex(['establishment_id']);
        });

        // Retorna para Varchar
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE paytime_transactions MODIFY establishment_id VARCHAR(255)');

        Schema::table('paytime_transactions', function (Blueprint $table) {
            $table->index('establishment_id');
        });
    }
};
