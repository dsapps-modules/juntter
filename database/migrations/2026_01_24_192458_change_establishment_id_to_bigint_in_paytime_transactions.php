<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('paytime_transactions') || ! Schema::hasColumn('paytime_transactions', 'establishment_id')) {
            return;
        }

        $this->removerIndiceEstablishmentId();

        match (DB::getDriverName()) {
            'mysql' => DB::statement('ALTER TABLE paytime_transactions MODIFY establishment_id BIGINT(20) UNSIGNED NULL'),
            'pgsql' => DB::statement('ALTER TABLE paytime_transactions ALTER COLUMN establishment_id TYPE BIGINT USING NULLIF(establishment_id, \'\')::BIGINT'),
            default => null,
        };

        $this->adicionarIndiceEstablishmentId();
    }

    public function down(): void
    {
        if (! Schema::hasTable('paytime_transactions') || ! Schema::hasColumn('paytime_transactions', 'establishment_id')) {
            return;
        }

        $this->removerIndiceEstablishmentId();

        match (DB::getDriverName()) {
            'mysql' => DB::statement('ALTER TABLE paytime_transactions MODIFY establishment_id VARCHAR(255) NULL'),
            'pgsql' => DB::statement('ALTER TABLE paytime_transactions ALTER COLUMN establishment_id TYPE VARCHAR(255)'),
            default => null,
        };

        $this->adicionarIndiceEstablishmentId();
    }

    private function adicionarIndiceEstablishmentId(): void
    {
        try {
            Schema::table('paytime_transactions', function (Blueprint $table) {
                $table->index('establishment_id');
            });
        } catch (\Throwable) {
        }
    }

    private function removerIndiceEstablishmentId(): void
    {
        try {
            Schema::table('paytime_transactions', function (Blueprint $table) {
                $table->dropIndex(['establishment_id']);
            });
        } catch (\Throwable) {
        }
    }
};
