<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('links_pagamento') || Schema::hasColumn('links_pagamento', 'is_avista')) {
            return;
        }

        Schema::table('links_pagamento', function (Blueprint $table) {
            $table->boolean('is_avista')->default(false)->after('parcelas');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('links_pagamento') || ! Schema::hasColumn('links_pagamento', 'is_avista')) {
            return;
        }

        Schema::table('links_pagamento', function (Blueprint $table) {
            $table->dropColumn('is_avista');
        });
    }
};
