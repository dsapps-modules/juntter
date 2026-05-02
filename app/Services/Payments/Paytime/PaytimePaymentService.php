<?php

namespace App\Services\Payments\Paytime;

use App\Models\Order;
use App\Services\Payments\PaymentGatewayInterface;

class PaytimePaymentService implements PaymentGatewayInterface
{
    public function __construct(private readonly PaytimeClient $client) {}

    public function createPixPayment(Order $order): array
    {
        return $this->client->createPixPayment($order);
    }

    public function createBoletoPayment(Order $order): array
    {
        return $this->client->createBoletoPayment($order);
    }

    public function createCreditCardPayment(Order $order, array $cardData): array
    {
        return $this->client->createCreditCardPayment($order, $cardData);
    }

    public function parseWebhook(array $payload, array $headers): array
    {
        return $this->client->parseWebhook($payload, $headers);
    }
}
