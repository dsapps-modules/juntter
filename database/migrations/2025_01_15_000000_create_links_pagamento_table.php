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
        Schema::create('links_pagamento', function (Blueprint $table) {
            $table->id();
            $table->string('estabelecimento_id'); // ID da API externa
            $table->string('codigo_unico')->unique();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->decimal('valor', 10, 2);
            $table->bigInteger('valor_centavos');
            $table->json('parcelas')->nullable(); // Array com opções de parcelamento
            $table->enum('juros', ['CLIENT', 'ESTABLISHMENT'])->default('CLIENT');
            $table->enum('status', ['ATIVO', 'INATIVO', 'EXPIRADO', 'PAID'])->default('ATIVO');
            $table->timestamp('data_expiracao')->nullable();
            $table->json('dados_cliente')->nullable(); // Dados opcionais do cliente
            $table->string('url_retorno')->nullable(); // URL de retorno após pagamento
            $table->string('url_webhook')->nullable(); // URL para webhook            
            $table->index(['estabelecimento_id', 'status']);
            $table->index('codigo_unico');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links_pagamento');
    }
};