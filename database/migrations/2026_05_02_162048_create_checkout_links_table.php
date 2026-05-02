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
        if (Schema::hasTable('checkout_links')) {
            return;
        }

        Schema::create('checkout_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('public_token')->unique();
            $table->string('name');
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->boolean('allow_pix')->default(true);
            $table->boolean('allow_boleto')->default(true);
            $table->boolean('allow_credit_card')->default(true);
            $table->enum('pix_discount_type', ['none', 'fixed', 'percentage'])->default('none');
            $table->decimal('pix_discount_value', 12, 2)->default(0);
            $table->enum('boleto_discount_type', ['none', 'fixed', 'percentage'])->default('none');
            $table->decimal('boleto_discount_value', 12, 2)->default(0);
            $table->boolean('free_shipping')->default(true);
            $table->string('success_url')->nullable();
            $table->string('failure_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('visual_config')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout_links');
    }
};
