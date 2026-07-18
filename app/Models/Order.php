<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'checkout_link_id',
        'checkout_session_id',
        'product_id',
        'order_number',
        'status',
        'customer_name',
        'customer_email',
        'customer_document',
        'customer_phone',
        'billing_zipcode',
        'billing_street',
        'billing_number',
        'billing_complement',
        'billing_neighborhood',
        'billing_city',
        'billing_state',
        'delivery_zipcode',
        'delivery_street',
        'delivery_number',
        'delivery_complement',
        'delivery_neighborhood',
        'delivery_city',
        'delivery_state',
        'quantity',
        'unit_price',
        'subtotal',
        'discount_total',
        'shipping_total',
        'total',
        'payment_method',
        'shipping_option_id',
        'shipping_option_name',
        'success_url_used',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'total' => 'decimal:2',
        'shipping_option_id' => 'integer',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function checkoutLink(): BelongsTo
    {
        return $this->belongsTo(CheckoutLink::class);
    }

    public function checkoutSession(): BelongsTo
    {
        return $this->belongsTo(CheckoutSession::class);
    }

    public function shippingOption(): BelongsTo
    {
        return $this->belongsTo(CheckoutShippingOption::class, 'shipping_option_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function paymentTransaction(): HasOne
    {
        return $this->hasOne(PaymentTransaction::class);
    }
}
