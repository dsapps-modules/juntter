<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('checkout_sessions')) {
            Schema::table('checkout_sessions', function (Blueprint $table): void {
                if (! Schema::hasColumn('checkout_sessions', 'shipping_option_id')) {
                    $table->unsignedBigInteger('shipping_option_id')->nullable()->index()->after('payment_method');
                }

                if (! Schema::hasColumn('checkout_sessions', 'shipping_option_name')) {
                    $table->string('shipping_option_name')->nullable()->after('shipping_option_id');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                if (! Schema::hasColumn('orders', 'shipping_option_id')) {
                    $table->unsignedBigInteger('shipping_option_id')->nullable()->index()->after('payment_method');
                }

                if (! Schema::hasColumn('orders', 'shipping_option_name')) {
                    $table->string('shipping_option_name')->nullable()->after('shipping_option_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('checkout_sessions')) {
            Schema::table('checkout_sessions', function (Blueprint $table): void {
                if (Schema::hasColumn('checkout_sessions', 'shipping_option_id')) {
                    try {
                        $table->dropIndex(['shipping_option_id']);
                    } catch (\Throwable) {
                    }
                }

                if (Schema::hasColumn('checkout_sessions', 'shipping_option_name')) {
                    $table->dropColumn('shipping_option_name');
                }

                if (Schema::hasColumn('checkout_sessions', 'shipping_option_id')) {
                    $table->dropColumn('shipping_option_id');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                if (Schema::hasColumn('orders', 'shipping_option_id')) {
                    try {
                        $table->dropIndex(['shipping_option_id']);
                    } catch (\Throwable) {
                    }
                }

                if (Schema::hasColumn('orders', 'shipping_option_name')) {
                    $table->dropColumn('shipping_option_name');
                }

                if (Schema::hasColumn('orders', 'shipping_option_id')) {
                    $table->dropColumn('shipping_option_id');
                }
            });
        }
    }
};
