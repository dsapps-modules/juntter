<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('paytime_transactions')) {
            return;
        }

        $this->adicionarIndice('created_at');
        $this->adicionarIndice('establishment_id');
    }

    public function down(): void
    {
        if (! Schema::hasTable('paytime_transactions')) {
            return;
        }

        $this->removerIndice('created_at');
        $this->removerIndice('establishment_id');
    }

    private function adicionarIndice(string $column): void
    {
        if (! Schema::hasColumn('paytime_transactions', $column)) {
            return;
        }

        try {
            Schema::table('paytime_transactions', function (Blueprint $table) use ($column) {
                $table->index($column);
            });
        } catch (\Throwable) {
        }
    }

    private function removerIndice(string $column): void
    {
        if (! Schema::hasColumn('paytime_transactions', $column)) {
            return;
        }

        try {
            Schema::table('paytime_transactions', function (Blueprint $table) use ($column) {
                $table->dropIndex([$column]);
            });
        } catch (\Throwable) {
        }
    }
};
