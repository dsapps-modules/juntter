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
        if (Schema::hasTable('payment_transactions')) {
            return;
        }

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('seller_id')->index();
            $table->enum('gateway', ['paytime'])->default('paytime');
            $table->string('gateway_transaction_id')->nullable()->index();
            $table->string('gateway_status')->nullable();
            $table->enum('internal_status', ['pending', 'authorized', 'paid', 'failed', 'cancelled', 'expired', 'refunded'])->default('pending');
            $table->enum('payment_method', ['pix', 'boleto', 'credit_card']);
            $table->decimal('amount', 12, 2);
            $table->text('pix_qr_code')->nullable();
            $table->text('pix_copy_paste')->nullable();
            $table->timestamp('pix_expires_at')->nullable();
            $table->string('boleto_url')->nullable();
            $table->string('boleto_barcode')->nullable();
            $table->string('boleto_digitable_line')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_brand')->nullable();
            $table->unsignedTinyInteger('installments')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('webhook_payload')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'gateway_transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
