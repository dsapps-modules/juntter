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
        Schema::create('checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('checkout_link_id')->index();
            $table->unsignedBigInteger('seller_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('session_token')->unique();
            $table->enum('status', ['started', 'identification_completed', 'delivery_completed', 'payment_started', 'payment_pending', 'paid', 'abandoned', 'cancelled', 'failed'])->default('started');
            $table->enum('current_step', ['identification', 'delivery', 'payment', 'confirmation'])->default('identification');
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_document')->nullable();
            $table->enum('customer_document_type', ['cpf', 'cnpj'])->nullable();
            $table->string('customer_phone')->nullable();
            $table->date('customer_birth_date')->nullable();
            $table->string('customer_company_name')->nullable();
            $table->string('customer_state_registration')->nullable();
            $table->boolean('customer_is_state_registration_exempt')->default(false);
            $table->string('zipcode')->nullable();
            $table->string('street')->nullable();
            $table->string('number')->nullable();
            $table->string('complement')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('recipient_name')->nullable();
            $table->enum('payment_method', ['pix', 'boleto', 'credit_card'])->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout_sessions');
    }
};
