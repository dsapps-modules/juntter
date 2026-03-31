<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('links_pagamento') || ! Schema::hasColumn('links_pagamento', 'titulo')) {
            return;
        }

        Schema::table('links_pagamento', function (Blueprint $table) {
            $table->dropColumn('titulo');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('links_pagamento') || Schema::hasColumn('links_pagamento', 'titulo')) {
            return;
        }

        Schema::table('links_pagamento', function (Blueprint $table) {
            $table->string('titulo')->after('codigo_unico');
        });
    }
};
