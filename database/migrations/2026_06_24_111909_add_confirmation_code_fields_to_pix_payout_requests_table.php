<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pix_payout_requests', function (Blueprint $table): void {
            $table->text('confirmation_code_hash')->nullable()->after('expires_at');
            $table->unsignedTinyInteger('confirmation_code_attempts')->default(0)->after('confirmation_code_hash');
            $table->timestamp('confirmation_code_sent_at')->nullable()->after('confirmation_code_attempts');
            $table->timestamp('confirmation_code_expires_at')->nullable()->after('confirmation_code_sent_at');
            $table->timestamp('confirmation_code_verified_at')->nullable()->after('confirmation_code_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('pix_payout_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'confirmation_code_hash',
                'confirmation_code_attempts',
                'confirmation_code_sent_at',
                'confirmation_code_expires_at',
                'confirmation_code_verified_at',
            ]);
        });
    }
};
