<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'name',
        'slug',
        'description',
        'short_description',
        'sku',
        'image_path',
        'price',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    protected $appends = [
        'image_url',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function checkoutLinks(): HasMany
    {
        return $this->hasMany(CheckoutLink::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! filled($this->image_path)) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://') || str_starts_with($this->image_path, '/')) {
            return $this->image_path;
        }

        return route('seller.products.image', ['product' => $this->getKey()]);
    }
}
