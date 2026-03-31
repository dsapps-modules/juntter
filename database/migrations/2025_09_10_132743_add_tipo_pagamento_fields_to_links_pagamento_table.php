<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('links_pagamento')) {
            return;
        }

        Schema::table('links_pagamento', function (Blueprint $table) {
            if (! Schema::hasColumn('links_pagamento', 'tipo_pagamento')) {
                $table->string('tipo_pagamento')->default('CARTAO')->after('status');
            }

            if (! Schema::hasColumn('links_pagamento', 'data_vencimento')) {
                $table->date('data_vencimento')->nullable()->after('data_expiracao');
            }

            if (! Schema::hasColumn('links_pagamento', 'data_limite_pagamento')) {
                $table->date('data_limite_pagamento')->nullable()->after('data_vencimento');
            }

            if (! Schema::hasColumn('links_pagamento', 'instrucoes_boleto')) {
                $table->json('instrucoes_boleto')->nullable()->after('dados_cliente');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('links_pagamento')) {
            return;
        }

        Schema::table('links_pagamento', function (Blueprint $table) {
            $columnsToDrop = [];

            foreach (['tipo_pagamento', 'data_vencimento', 'data_limite_pagamento', 'instrucoes_boleto'] as $column) {
                if (Schema::hasColumn('links_pagamento', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
