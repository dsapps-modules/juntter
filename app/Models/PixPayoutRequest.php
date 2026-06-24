<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PixPayoutRequest extends Model
{
    use HasFactory;

    protected $table = 'pix_payout_requests';

    protected $fillable = [
        'seller_id',
        'establishment_id',
        'amount',
        'pix_key_type',
        'pix_key',
        'hash_code',
        'description',
        'status',
        'init_id',
        'gateway_authorization',
        'pin_hash',
        'pin_attempts',
        'pin_expires_at',
        'expires_at',
        'confirmation_code_hash',
        'confirmation_code_attempts',
        'confirmation_code_sent_at',
        'confirmation_code_expires_at',
        'confirmation_code_verified_at',
        'init_payload',
        'init_response',
        'confirm_payload',
        'confirm_response',
        'last_error',
        'confirmed_at',
        'webhook_payload',
        'gateway_transaction_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'confirmation_code_attempts' => 'integer',
        'confirmation_code_sent_at' => 'datetime',
        'confirmation_code_expires_at' => 'datetime',
        'confirmation_code_verified_at' => 'datetime',
        'pin_attempts' => 'integer',
        'pin_expires_at' => 'datetime',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'init_payload' => 'array',
        'init_response' => 'array',
        'confirm_payload' => 'array',
        'confirm_response' => 'array',
        'webhook_payload' => 'array',
        'pix_key' => 'encrypted',
        'hash_code' => 'encrypted',
        'pin_hash' => 'encrypted',
        'confirmation_code_hash' => 'encrypted',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
