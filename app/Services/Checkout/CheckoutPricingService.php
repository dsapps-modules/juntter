<?php

namespace App\Services\Checkout;

use App\Models\CheckoutLink;

class CheckoutPricingService
{
    public function calculate(CheckoutLink $checkoutLink, string $paymentMethod): array
    {
        $quantity = max(1, (int) $checkoutLink->quantity);
        $unitPrice = (float) $checkoutLink->unit_price;
        $subtotal = round($quantity * $unitPrice, 2);
        $discount = $this->calculateDiscount($checkoutLink, $paymentMethod, $subtotal);
        $shipping = $checkoutLink->free_shipping ? 0.0 : 0.0;
        $total = round(max(0, $subtotal - $discount + $shipping), 2);

        return [
            'quantity' => $quantity,
            'unit_price' => round($unitPrice, 2),
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'shipping_total' => $shipping,
            'total' => $total,
        ];
    }

    private function calculateDiscount(CheckoutLink $checkoutLink, string $paymentMethod, float $subtotal): float
    {
        $discountType = null;
        $discountValue = 0.0;

        if ($paymentMethod === 'pix') {
            $discountType = $checkoutLink->pix_discount_type;
            $discountValue = (float) $checkoutLink->pix_discount_value;
        }

        if ($paymentMethod === 'boleto') {
            $discountType = $checkoutLink->boleto_discount_type;
            $discountValue = (float) $checkoutLink->boleto_discount_value;
        }

        if ($discountType === 'fixed') {
            return round(min($subtotal, $discountValue), 2);
        }

        if ($discountType === 'percentage') {
            return round(($subtotal * $discountValue) / 100, 2);
        }

        return 0.0;
    }
}
