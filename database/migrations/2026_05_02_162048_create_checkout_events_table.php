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
        if (Schema::hasTable('checkout_events')) {
            return;
        }

        Schema::create('checkout_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('checkout_session_id')->nullable()->index();
            $table->unsignedBigInteger('checkout_link_id')->index();
            $table->unsignedBigInteger('seller_id')->index();
            $table->string('event_type');
            $table->string('step')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout_events');
    }
};
