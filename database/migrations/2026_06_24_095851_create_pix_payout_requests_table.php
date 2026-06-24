<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pix_payout_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('establishment_id')->nullable()->index();
            $table->unsignedBigInteger('amount');
            $table->string('pix_key_type', 20);
            $table->text('pix_key')->nullable();
            $table->text('hash_code')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->string('init_id')->nullable()->unique();
            $table->string('gateway_authorization')->nullable();
            $table->text('pin_hash')->nullable();
            $table->unsignedTinyInteger('pin_attempts')->default(0);
            $table->timestamp('pin_expires_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('init_payload')->nullable();
            $table->json('init_response')->nullable();
            $table->json('confirm_payload')->nullable();
            $table->json('confirm_response')->nullable();
            $table->json('webhook_payload')->nullable();
            $table->string('gateway_transaction_id')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pix_payout_requests');
    }
};
