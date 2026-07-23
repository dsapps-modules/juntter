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

    public function test_transactions_command_syncs_a_specific_day_using_an_entire_day_range(): void
    {
        $transacaoService = $this->createMock(TransacaoService::class);
        $syncService = $this->createMock(PaytimeTransactionSyncService::class);

        $item = [
            '_id' => 'transaction-123',
            'status' => 'PAID',
            'amount' => 15750,
            'created_at' => '2026-04-14T12:05:00.000Z',
        ];

        $callCount = 0;

        $transacaoService->expects($this->exactly(2))
            ->method('listarTransacoes')
            ->willReturnCallback(function (array $filters) use (&$callCount, $item): array {
                $callCount++;

                $decodedFilters = json_decode($filters['filters'] ?? '[]', true);

                $this->assertSame('2026-04-14 00:00:00', $decodedFilters['created_at']['min'] ?? null);
                $this->assertSame('2026-04-14 23:59:59', $decodedFilters['created_at']['max'] ?? null);

                return $callCount === 1
                    ? ['data' => [$item]]
                    : ['data' => []];
            });

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
            '--date' => '2026-04-14',
        ]);
    }

    public function test_transactions_command_continues_when_one_page_fails(): void
    {
        $transacaoService = $this->createMock(TransacaoService::class);
        $syncService = $this->createMock(PaytimeTransactionSyncService::class);

        $firstItem = [
            '_id' => 'transaction-123',
            'status' => 'PAID',
            'amount' => 15750,
            'created_at' => '2026-04-14T12:05:00.000Z',
        ];

        $thirdItem = [
            '_id' => 'transaction-456',
            'status' => 'PAID',
            'amount' => 23000,
            'created_at' => '2026-04-15T12:05:00.000Z',
        ];

        $callCount = 0;

        $transacaoService->expects($this->exactly(4))
            ->method('listarTransacoes')
            ->willReturnCallback(function () use (&$callCount, $firstItem, $thirdItem): array {
                $callCount++;

                return match ($callCount) {
                    1 => ['data' => [$firstItem]],
                    2 => throw new \TypeError('Return value must be of type array, null returned'),
                    3 => ['data' => [$thirdItem]],
                    default => ['data' => []],
                };
            });

        $syncCallCount = 0;

        $syncService->expects($this->exactly(2))
            ->method('sync')
            ->willReturnCallback(function (array $item, array $context) use (&$syncCallCount, $firstItem, $thirdItem): void {
                $syncCallCount++;

                if ($syncCallCount === 1) {
                    $this->assertSame($firstItem, $item);
                    $this->assertSame('UNKNOWN', $context['default_type'] ?? null);
                    $this->assertSame($firstItem['created_at'], $context['created_at'] ?? null);
                    $this->assertSame($firstItem, $context['metadata'] ?? null);

                    return;
                }

                $this->assertSame($thirdItem, $item);
                $this->assertSame('UNKNOWN', $context['default_type'] ?? null);
                $this->assertSame($thirdItem['created_at'], $context['created_at'] ?? null);
                $this->assertSame($thirdItem, $context['metadata'] ?? null);
            });

        $this->app->instance(TransacaoService::class, $transacaoService);
        $this->app->instance(PaytimeTransactionSyncService::class, $syncService);

        $exitCode = Artisan::call('paytime:sync-transactions', [
            '--months' => '4',
            '--year' => '2026',
        ]);

        $this->assertSame(0, $exitCode);
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

    public function test_billets_command_syncs_a_specific_day_using_an_entire_day_range(): void
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

        $callCount = 0;

        $boletoService->expects($this->exactly(2))
            ->method('listarBoletos')
            ->willReturnCallback(function (array $filters) use (&$callCount, $item): array {
                $callCount++;

                $decodedFilters = json_decode($filters['filters'] ?? '[]', true);

                $this->assertSame('2026-04-14 00:00:00', $decodedFilters['created_at']['min'] ?? null);
                $this->assertSame('2026-04-14 23:59:59', $decodedFilters['created_at']['max'] ?? null);

                return $callCount === 1
                    ? ['data' => [$item]]
                    : ['data' => []];
            });

        $syncService->expects($this->once())
            ->method('sync')
            ->with(
                $this->equalTo($item),
                $this->callback(function (array $context): bool {
                    return ($context['default_type'] ?? null) === 'BILLET'
                        && ($context['default_establishment_id'] ?? null) === 155463
                        && ($context['metadata'] ?? null) === [
                            '_id' => 'billet-123',
                            'status' => 'PAID',
                            'amount' => 9900,
                            'created_at' => '2026-04-14T12:05:00.000Z',
                        ];
                })
            );

        $this->app->instance(BoletoService::class, $boletoService);
        $this->app->instance(PaytimeTransactionSyncService::class, $syncService);

        Artisan::call('paytime:sync-billets', [
            '--date' => '2026-04-14',
        ]);
    }

    public function test_billets_command_continues_when_one_page_fails(): void
    {
        PaytimeEstablishment::query()->create([
            'id' => 155463,
            'active' => true,
        ]);

        $boletoService = $this->createMock(BoletoService::class);
        $syncService = $this->createMock(PaytimeTransactionSyncService::class);

        $firstItem = [
            '_id' => 'billet-123',
            'status' => 'PAID',
            'amount' => 9900,
            'created_at' => '2026-04-14T12:05:00.000Z',
        ];

        $thirdItem = [
            '_id' => 'billet-456',
            'status' => 'PAID',
            'amount' => 14500,
            'created_at' => '2026-04-15T12:05:00.000Z',
        ];

        $callCount = 0;

        $boletoService->expects($this->exactly(4))
            ->method('listarBoletos')
            ->willReturnCallback(function () use (&$callCount, $firstItem, $thirdItem): array {
                $callCount++;

                return match ($callCount) {
                    1 => ['data' => [$firstItem]],
                    2 => throw new \TypeError('Return value must be of type array, null returned'),
                    3 => ['data' => [$thirdItem]],
                    default => ['data' => []],
                };
            });

        $syncCallCount = 0;

        $syncService->expects($this->exactly(2))
            ->method('sync')
            ->willReturnCallback(function (array $item, array $context) use (&$syncCallCount, $firstItem, $thirdItem): void {
                $syncCallCount++;

                if ($syncCallCount === 1) {
                    $this->assertSame($firstItem, $item);
                    $this->assertSame('BILLET', $context['default_type'] ?? null);
                    $this->assertSame(155463, $context['default_establishment_id'] ?? null);
                    $this->assertSame($firstItem['created_at'], $context['created_at'] ?? null);
                    $this->assertSame($firstItem, $context['metadata'] ?? null);

                    return;
                }

                $this->assertSame($thirdItem, $item);
                $this->assertSame('BILLET', $context['default_type'] ?? null);
                $this->assertSame(155463, $context['default_establishment_id'] ?? null);
                $this->assertSame($thirdItem['created_at'], $context['created_at'] ?? null);
                $this->assertSame($thirdItem, $context['metadata'] ?? null);
            });

        $this->app->instance(BoletoService::class, $boletoService);
        $this->app->instance(PaytimeTransactionSyncService::class, $syncService);

        $exitCode = Artisan::call('paytime:sync-billets', [
            '--months' => '4',
            '--year' => '2026',
        ]);

        $this->assertSame(0, $exitCode);
    }
}
