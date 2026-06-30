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
        Schema::create('recorrencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('estabelecimento_id');
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_document')->nullable();
            $table->string('payment_type', 20);
            $table->decimal('amount', 10, 2);
            $table->bigInteger('amount_centavos');
            $table->string('frequency', 20);
            $table->unsignedTinyInteger('charge_day')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('payment_link_url');
            $table->boolean('send_via_email')->default(true);
            $table->boolean('send_via_whatsapp')->default(false);
            $table->string('recipient_email')->nullable();
            $table->string('email_subject')->nullable();
            $table->text('email_message')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('status')->default('PENDENTE');
            $table->json('metadata')->nullable();
            $table->index(['estabelecimento_id', 'payment_type']);
            $table->index(['user_id', 'status']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recorrencias');
    }
};
