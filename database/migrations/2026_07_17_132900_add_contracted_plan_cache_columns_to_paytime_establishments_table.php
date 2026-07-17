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
        Schema::table('paytime_establishments', function (Blueprint $table) {
            $table->json('contracted_plan_json')->nullable()->after('pricing_snapshot_json');
            $table->string('contracted_plan_snapshot_hash', 64)->nullable()->after('contracted_plan_json');
            $table->timestamp('contracted_plan_source_updated_at')->nullable()->after('contracted_plan_snapshot_hash');
            $table->timestamp('contracted_plan_synced_at')->nullable()->after('contracted_plan_source_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paytime_establishments', function (Blueprint $table) {
            $table->dropColumn([
                'contracted_plan_json',
                'contracted_plan_snapshot_hash',
                'contracted_plan_source_updated_at',
                'contracted_plan_synced_at',
            ]);
        });
    }
};
