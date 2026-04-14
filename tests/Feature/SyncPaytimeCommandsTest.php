<?php

namespace Tests\Feature;

use App\Models\PaytimeEstablishment;
use App\Services\BoletoService;
use App\Services\PaytimeTransactionSyncService;
use App\Services\TransacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SyncPaytimeCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_command_forwards_api_items_to_the_shared_sync_service(): void
    {
        $transacaoService = $this->createMock(TransacaoService::class);
        $syncService = $this->createMock(PaytimeTransactionSyncService::class);

        $item = [
            '_id' => 'transaction-123',
            'status' => 'PAID',
            'amount' => 15750,
            'created_at' => '2026-04-14T12:05:00.000Z',
        ];

        $transacaoService->expects($this->exactly(2))
            ->method('listarTransacoes')
            ->willReturnOnConsecutiveCalls(
                ['data' => [$item]],
                ['data' => []]
            );

        $syncService->expects($this->once())
            ->method('sync')
            ->with(
                $this->equalTo($item),
                $this->callback(function (array $context) use ($item): bool {
                    return ($context['default_type'] ?? null) === 'UNKNOWN'
                        && ($context['created_at'] ?? null) === $item['created_at']
                        && ($context['metadata'] ?? null) === $item;
                })
            );

        $this->app->instance(TransacaoService::class, $transacaoService);
        $this->app->instance(PaytimeTransactionSyncService::class, $syncService);

        Artisan::call('paytime:sync-transactions', [
            '--months' => '4',
            '--year' => '2026',
        ]);
    }

    public function test_billets_command_forwards_api_items_to_the_shared_sync_service(): void
    {
        PaytimeEstablishment::query()->create([
            'id' => 155463,
            'active' => true,
        ]);

        $boletoService = $this->createMock(BoletoService::class);
        $syncService = $this->createMock(PaytimeTransactionSyncService::class);

        $item = [
            '_id' => 'billet-123',
            'status' => 'PAID',
            'amount' => 9900,
            'created_at' => '2026-04-14T12:05:00.000Z',
        ];

        $boletoService->expects($this->exactly(2))
            ->method('listarBoletos')
            ->willReturnOnConsecutiveCalls(
                ['data' => [$item]],
                ['data' => []]
            );

        $syncService->expects($this->once())
            ->method('sync')
            ->with(
                $this->equalTo($item),
                $this->callback(function (array $context) use ($item): bool {
                    return ($context['default_type'] ?? null) === 'BILLET'
                        && ($context['default_establishment_id'] ?? null) === 155463
                        && ($context['created_at'] ?? null) === $item['created_at']
                        && ($context['metadata'] ?? null) === $item;
                })
            );

        $this->app->instance(BoletoService::class, $boletoService);
        $this->app->instance(PaytimeTransactionSyncService::class, $syncService);

        Artisan::call('paytime:sync-billets', [
            '--months' => '4',
            '--year' => '2026',
        ]);
    }
}
