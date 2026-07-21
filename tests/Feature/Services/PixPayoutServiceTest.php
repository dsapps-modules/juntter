<?php

namespace Tests\Feature\Services;

use App\Services\ApiClientService;
use App\Services\PixPayoutService;
use Tests\TestCase;

class PixPayoutServiceTest extends TestCase
{
    public function test_initiate_uses_the_paytime_payout_base_url(): void
    {
        config()->set('services.paytime.payout_base_url', 'https://banking.paytime.com.br/v1');

        $apiClient = $this->createMock(ApiClientService::class);
        $apiClient->expects($this->once())
            ->method('post')
            ->with(
                'marketplace/banking/transfers/pix-init',
                ['document' => '40400554895'],
                'https://banking.paytime.com.br/v1',
            )
            ->willReturn(['status' => 'ok']);

        $service = new PixPayoutService($apiClient);
        $response = $service->initiate(['document' => '40400554895']);

        $this->assertSame(['status' => 'ok'], $response);
    }

    public function test_confirm_uses_the_paytime_payout_base_url(): void
    {
        config()->set('services.paytime.payout_base_url', 'https://banking.paytime.com.br/v1');

        $apiClient = $this->createMock(ApiClientService::class);
        $apiClient->expects($this->once())
            ->method('post')
            ->with(
                'marketplace/banking/transfers/pix-confirm',
                ['external_number' => '7'],
                'https://banking.paytime.com.br/v1',
            )
            ->willReturn(['status' => 'ok']);

        $service = new PixPayoutService($apiClient);
        $response = $service->confirm(['external_number' => '7']);

        $this->assertSame(['status' => 'ok'], $response);
    }
}
