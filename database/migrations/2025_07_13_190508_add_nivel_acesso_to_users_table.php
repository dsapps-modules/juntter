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
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'nivel_acesso')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('nivel_acesso', ['super_admin', 'admin', 'vendedor'])->default('vendedor')->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'nivel_acesso')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nivel_acesso');
        });
    }
};
