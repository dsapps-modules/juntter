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
        if (! Schema::hasTable('vendedores') || Schema::hasColumn('vendedores', 'must_change_password')) {
            return;
        }

        Schema::table('vendedores', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('vendedores') || ! Schema::hasColumn('vendedores', 'must_change_password')) {
            return;
        }

        Schema::table('vendedores', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};
