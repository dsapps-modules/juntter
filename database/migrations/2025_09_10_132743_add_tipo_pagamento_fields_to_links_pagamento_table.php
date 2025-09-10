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
        Schema::table('links_pagamento', function (Blueprint $table) {
            $table->string('tipo_pagamento')->default('CARTAO')->after('status');
            $table->date('data_vencimento')->nullable()->after('data_expiracao');
            $table->date('data_limite_pagamento')->nullable()->after('data_vencimento');
            $table->json('instrucoes_boleto')->nullable()->after('dados_cliente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('links_pagamento', function (Blueprint $table) {
            $table->dropColumn(['tipo_pagamento', 'data_vencimento', 'data_limite_pagamento', 'instrucoes_boleto']);
        });
    }
};
