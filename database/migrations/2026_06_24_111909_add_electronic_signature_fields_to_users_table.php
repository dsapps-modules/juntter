<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('electronic_signature_hash')->nullable()->after('company_logo_path');
            $table->text('electronic_signature_pending_hash')->nullable()->after('electronic_signature_hash');
            $table->text('electronic_signature_code_hash')->nullable()->after('electronic_signature_pending_hash');
            $table->unsignedTinyInteger('electronic_signature_code_attempts')->default(0)->after('electronic_signature_code_hash');
            $table->timestamp('electronic_signature_code_sent_at')->nullable()->after('electronic_signature_code_attempts');
            $table->timestamp('electronic_signature_code_expires_at')->nullable()->after('electronic_signature_code_sent_at');
            $table->timestamp('electronic_signature_verified_at')->nullable()->after('electronic_signature_code_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'electronic_signature_hash',
                'electronic_signature_pending_hash',
                'electronic_signature_code_hash',
                'electronic_signature_code_attempts',
                'electronic_signature_code_sent_at',
                'electronic_signature_code_expires_at',
                'electronic_signature_verified_at',
            ]);
        });
    }
};
