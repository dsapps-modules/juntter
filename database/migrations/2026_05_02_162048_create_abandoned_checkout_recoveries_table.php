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
        if (Schema::hasTable('abandoned_checkout_recoveries')) {
            return;
        }

        Schema::create('abandoned_checkout_recoveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('checkout_session_id')->index();
            $table->unsignedBigInteger('seller_id')->index();
            $table->enum('channel', ['email', 'whatsapp']);
            $table->enum('status', ['pending', 'sent', 'failed', 'skipped'])->default('pending');
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['checkout_session_id', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abandoned_checkout_recoveries');
    }
};
