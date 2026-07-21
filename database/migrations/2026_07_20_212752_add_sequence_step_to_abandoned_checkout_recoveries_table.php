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
        Schema::table('abandoned_checkout_recoveries', function (Blueprint $table) {
            $table->unsignedTinyInteger('sequence_step')->default(1)->after('channel');
            $table->unique(['checkout_session_id', 'channel', 'sequence_step'], 'acr_session_channel_step_unique');
            $table->index(['status', 'scheduled_at'], 'acr_status_scheduled_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abandoned_checkout_recoveries', function (Blueprint $table) {
            $table->dropUnique('acr_session_channel_step_unique');
            $table->dropIndex('acr_status_scheduled_at_index');
            $table->dropColumn('sequence_step');
        });
    }
};
