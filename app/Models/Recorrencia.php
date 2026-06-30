<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recorrencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'estabelecimento_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_document',
        'payment_type',
        'amount',
        'amount_centavos',
        'frequency',
        'charge_day',
        'start_date',
        'end_date',
        'payment_link_url',
        'send_via_email',
        'send_via_whatsapp',
        'recipient_email',
        'email_subject',
        'email_message',
        'whatsapp_number',
        'status',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_centavos' => 'integer',
        'charge_day' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'send_via_email' => 'boolean',
        'send_via_whatsapp' => 'boolean',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
