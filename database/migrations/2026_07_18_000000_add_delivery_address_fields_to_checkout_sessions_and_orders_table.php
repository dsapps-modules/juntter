<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table): void {
            $table->string('delivery_zipcode', 10)->nullable()->after('recipient_name');
            $table->string('delivery_street')->nullable()->after('delivery_zipcode');
            $table->string('delivery_number', 20)->nullable()->after('delivery_street');
            $table->string('delivery_complement')->nullable()->after('delivery_number');
            $table->string('delivery_neighborhood')->nullable()->after('delivery_complement');
            $table->string('delivery_city')->nullable()->after('delivery_neighborhood');
            $table->string('delivery_state', 2)->nullable()->after('delivery_city');
            $table->string('delivery_recipient_name')->nullable()->after('delivery_state');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('billing_zipcode', 10)->nullable()->after('customer_phone');
            $table->string('billing_street')->nullable()->after('billing_zipcode');
            $table->string('billing_number', 20)->nullable()->after('billing_street');
            $table->string('billing_complement')->nullable()->after('billing_number');
            $table->string('billing_neighborhood')->nullable()->after('billing_complement');
            $table->string('billing_city')->nullable()->after('billing_neighborhood');
            $table->string('billing_state', 2)->nullable()->after('billing_city');
            $table->string('delivery_zipcode', 10)->nullable()->after('billing_state');
            $table->string('delivery_street')->nullable()->after('delivery_zipcode');
            $table->string('delivery_number', 20)->nullable()->after('delivery_street');
            $table->string('delivery_complement')->nullable()->after('delivery_number');
            $table->string('delivery_neighborhood')->nullable()->after('delivery_complement');
            $table->string('delivery_city')->nullable()->after('delivery_neighborhood');
            $table->string('delivery_state', 2)->nullable()->after('delivery_city');
        });
    }

    public function down(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table): void {
            $table->dropColumn([
                'delivery_zipcode',
                'delivery_street',
                'delivery_number',
                'delivery_complement',
                'delivery_neighborhood',
                'delivery_city',
                'delivery_state',
                'delivery_recipient_name',
            ]);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'billing_zipcode',
                'billing_street',
                'billing_number',
                'billing_complement',
                'billing_neighborhood',
                'billing_city',
                'billing_state',
                'delivery_zipcode',
                'delivery_street',
                'delivery_number',
                'delivery_complement',
                'delivery_neighborhood',
                'delivery_city',
                'delivery_state',
            ]);
        });
    }
};
