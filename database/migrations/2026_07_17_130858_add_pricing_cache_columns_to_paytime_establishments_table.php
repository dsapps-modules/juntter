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
            $table->json('plans_json')->nullable()->after('responsible_json');
            $table->json('fees_banking_json')->nullable()->after('plans_json');
            $table->json('pricing_snapshot_json')->nullable()->after('fees_banking_json');
            $table->string('pricing_snapshot_hash', 64)->nullable()->after('pricing_snapshot_json');
            $table->timestamp('pricing_source_updated_at')->nullable()->after('pricing_snapshot_hash');
            $table->timestamp('pricing_synced_at')->nullable()->after('pricing_source_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paytime_establishments', function (Blueprint $table) {
            $table->dropColumn([
                'plans_json',
                'fees_banking_json',
                'pricing_snapshot_json',
                'pricing_snapshot_hash',
                'pricing_source_updated_at',
                'pricing_synced_at',
            ]);
        });
    }
};
