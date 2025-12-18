<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaytimeTransaction extends Model
{
    use HasFactory;

    protected $table = 'paytime_transactions';

    protected $fillable = [
        'external_id',
        'establishment_id',
        'type',
        'status',
        'amount',
        'original_amount',
        'fees',
        'installments',
        'gateway_key',
        'authorization_code',
        'scheduled_at',
        'expiration_at',
        'paid_at',
        'customer_name',
        'customer_document',
        'metadata',
        'created_at', // Permitir setar manualmente ao sincronizar
        'updated_at'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'expiration_at' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
        'amount' => 'integer',
        'original_amount' => 'integer',
        'fees' => 'integer',
    ];

    /**
     * Escopo para filtrar por estabelecimento
     */
    public function scopeEstablishment($query, string $establishmentId)
    {
        return $query->where('establishment_id', $establishmentId);
    }

    /**
     * Escopo para filtrar por data de criação
     */
    public function scopePeriodo($query, $inicio, $fim)
    {
        return $query->whereBetween('created_at', [$inicio, $fim]);
    }

    /**
     * Accessor para retornar valor em reais (float) se necessário
     */
    public function getAmountFloatAttribute()
    {
        return $this->amount / 100;
    }

    public function getOriginalAmountFloatAttribute()
    {
        return $this->original_amount / 100;
    }
}
