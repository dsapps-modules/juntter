<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('paytime_establishments', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // ID da API é a chave primária
            $table->string('type')->nullable(); // INDIVIDUAL, COMPANY
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('fantasy_name')->nullable();
            $table->string('document')->nullable()->index(); // CPF/CNPJ
            $table->string('email')->nullable()->index();
            $table->string('phone_number')->nullable();
            $table->boolean('active')->default(true);
            $table->string('status')->nullable(); // APPROVED, etc
            $table->string('risk')->nullable(); // LOW, etc
            $table->string('category')->nullable();
            $table->string('code')->nullable();
            $table->decimal('revenue', 15, 2)->nullable(); // Faturamento informado

            // Campos JSON para dados complexos
            $table->json('address_json')->nullable();
            $table->json('responsible_json')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paytime_establishments');
    }
};
