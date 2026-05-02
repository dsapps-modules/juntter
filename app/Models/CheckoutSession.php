<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CheckoutSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkout_link_id',
        'seller_id',
        'product_id',
        'session_token',
        'status',
        'current_step',
        'customer_name',
        'customer_email',
        'customer_document',
        'customer_document_type',
        'customer_phone',
        'customer_birth_date',
        'customer_company_name',
        'customer_state_registration',
        'customer_is_state_registration_exempt',
        'zipcode',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'recipient_name',
        'payment_method',
        'subtotal',
        'discount_total',
        'shipping_total',
        'total',
        'metadata',
        'last_activity_at',
    ];

    protected $casts = [
        'customer_birth_date' => 'date',
        'customer_is_state_registration_exempt' => 'boolean',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'total' => 'decimal:2',
        'metadata' => 'array',
        'last_activity_at' => 'datetime',
    ];

    public function checkoutLink(): BelongsTo
    {
        return $this->belongsTo(CheckoutLink::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CheckoutEvent::class);
    }

    public function isAbandoned(): bool
    {
        return $this->status === 'abandoned';
    }

    public static function generateSessionToken(): string
    {
        return 'chs_'.Str::random(32);
    }

    public function touchActivity(): void
    {
        $this->forceFill(['last_activity_at' => now()])->save();
    }
}
