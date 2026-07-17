<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaytimeEstablishment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'paytime_establishments';

    public $incrementing = false; // ID da API não é auto-increment

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'type',
        'first_name',
        'last_name',
        'fantasy_name',
        'document',
        'email',
        'phone_number',
        'active',
        'status',
        'risk',
        'category',
        'code',
        'revenue',
        'address_json',
        'responsible_json',
        'plans_json',
        'fees_banking_json',
        'pricing_snapshot_json',
        'pricing_snapshot_hash',
        'pricing_source_updated_at',
        'pricing_synced_at',
        'contracted_plan_json',
        'contracted_plan_snapshot_hash',
        'contracted_plan_source_updated_at',
        'contracted_plan_synced_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'address_json' => 'array',
        'responsible_json' => 'array',
        'plans_json' => 'array',
        'fees_banking_json' => 'array',
        'pricing_snapshot_json' => 'array',
        'pricing_source_updated_at' => 'datetime',
        'pricing_synced_at' => 'datetime',
        'contracted_plan_json' => 'array',
        'contracted_plan_source_updated_at' => 'datetime',
        'contracted_plan_synced_at' => 'datetime',
        'revenue' => 'decimal:2',
    ];

    /**
     * Retorna o nome de exibição (Fantasia ou Nome Completo)
     */
    public function getDisplayNameAttribute()
    {
        if (! empty($this->fantasy_name)) {
            return $this->fantasy_name;
        }

        return trim("{$this->first_name} {$this->last_name}") ?: 'Sem Nome';
    }

    /**
     * Acessor para endereço formatado
     */
    public function getFormattedAddressAttribute()
    {
        $addr = $this->address_json;
        if (! $addr) {
            return 'N/A';
        }

        return sprintf(
            '%s, %s - %s, %s/%s',
            $addr['street'] ?? '',
            $addr['number'] ?? '',
            $addr['neighborhood'] ?? '',
            $addr['city'] ?? '',
            $addr['state'] ?? ''
        );
    }
}
