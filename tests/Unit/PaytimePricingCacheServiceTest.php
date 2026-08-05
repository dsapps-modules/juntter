<?php

namespace Tests\Unit;

use App\Models\PaytimeEstablishment;
use App\Services\EstabelecimentoService;
use App\Services\PaytimePricingCacheService;
use App\Services\TransacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaytimePricingCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_boleto_incoming_fee_is_fixed_and_does_not_reuse_pix_fee_snapshot(): void
    {
        config()->set('services.paytime.billet_fee_cents', 250);

        PaytimeEstablishment::query()->create([
            'id' => 5001,
            'type' => 'INDIVIDUAL',
            'first_name' => 'Isadora',
            'last_name' => 'Prado',
            'document' => '40400554895',
            'active' => true,
            'fees_banking_json' => [
                [
                    'fees' => [
                        'pix' => 100,
                        'dynamic_pix' => 125,
                    ],
                ],
            ],
        ]);

        $service = new PaytimePricingCacheService(
            $this->createMock(EstabelecimentoService::class),
            $this->createMock(TransacaoService::class),
        );

        $this->assertSame(250, $service->resolveBoletoIncomingFeeCents('5001'));
    }
}
