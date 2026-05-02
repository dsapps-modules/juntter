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
        if (Schema::hasTable('paytime_transactions')) {
            return;
        }

        Schema::create('paytime_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('establishment_id')->nullable();
            $table->string('type', 50)->default('UNKNOWN');
            $table->string('status', 50)->default('UNKNOWN');
            $table->bigInteger('amount')->default(0);
            $table->bigInteger('original_amount')->default(0);
            $table->bigInteger('fees')->default(0);
            $table->unsignedTinyInteger('installments')->default(1);
            $table->string('gateway_key')->nullable();
            $table->string('authorization_code')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('expiration_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_document')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paytime_transactions');
    }
};
