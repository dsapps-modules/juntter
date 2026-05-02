<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'seller_id',
        'gateway',
        'gateway_transaction_id',
        'gateway_status',
        'internal_status',
        'payment_method',
        'amount',
        'pix_qr_code',
        'pix_copy_paste',
        'pix_expires_at',
        'boleto_url',
        'boleto_barcode',
        'boleto_digitable_line',
        'card_last_four',
        'card_brand',
        'installments',
        'request_payload',
        'response_payload',
        'webhook_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'pix_expires_at' => 'datetime',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'webhook_payload' => 'array',
        'installments' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
