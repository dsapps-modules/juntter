<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CheckoutLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'product_id',
        'public_token',
        'name',
        'status',
        'quantity',
        'unit_price',
        'total_price',
        'allow_pix',
        'allow_boleto',
        'allow_credit_card',
        'pix_discount_type',
        'pix_discount_value',
        'boleto_discount_type',
        'boleto_discount_value',
        'free_shipping',
        'success_url',
        'failure_url',
        'expires_at',
        'visual_config',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'allow_pix' => 'boolean',
        'allow_boleto' => 'boolean',
        'allow_credit_card' => 'boolean',
        'pix_discount_value' => 'decimal:2',
        'boleto_discount_value' => 'decimal:2',
        'free_shipping' => 'boolean',
        'expires_at' => 'datetime',
        'visual_config' => 'array',
    ];

    public static function generatePublicToken(): string
    {
        return 'chk_'.Str::random(32);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CheckoutSession::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return $this->seller?->isVendedor() ?? false;
    }
}
