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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id')->index();
            $table->unsignedBigInteger('checkout_link_id')->index();
            $table->unsignedBigInteger('checkout_session_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('order_number')->unique();
            $table->enum('status', ['pending', 'paid', 'cancelled', 'failed', 'expired', 'refunded'])->default('pending');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_document');
            $table->string('customer_phone')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->enum('payment_method', ['pix', 'boleto', 'credit_card']);
            $table->string('success_url_used')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
