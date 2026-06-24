<?php

namespace App\Services;

class PixPayoutService
{
    public function __construct(
        private readonly ApiClientService $apiClient,
    ) {}

    public function initiate(array $payload): array
    {
        return $this->apiClient->post('marketplace/banking/transfers/pix-init', $payload);
    }

    public function confirm(array $payload): array
    {
        return $this->apiClient->post('marketplace/banking/transfers/pix-confirm', $payload);
    }
}
