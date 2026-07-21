<?php

namespace App\Services;

class PixPayoutService
{
    public function __construct(
        private readonly ApiClientService $apiClient,
    ) {}

    public function initiate(array $payload): array
    {
        return $this->apiClient->post('marketplace/banking/transfers/pix-init', $payload, $this->payoutBaseUrl());
    }

    public function confirm(array $payload): array
    {
        return $this->apiClient->post('marketplace/banking/transfers/pix-confirm', $payload, $this->payoutBaseUrl());
    }

    private function payoutBaseUrl(): string
    {
        return rtrim((string) config('services.paytime.payout_base_url', config('services.paytime.base_url')), '/');
    }
}
