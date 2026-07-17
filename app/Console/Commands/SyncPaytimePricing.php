<?php

namespace App\Console\Commands;

use App\Models\PaytimeEstablishment;
use App\Services\PaytimePricingCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPaytimePricing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paytime:sync-pricing {--establishment-id= : Sync only one establishment}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza o snapshot de planos e tarifas da Paytime para o banco local';

    /**
     * Execute the console command.
     */
    public function handle(PaytimePricingCacheService $pricingCacheService): int
    {
        $establishmentId = $this->option('establishment-id');
        $ids = [];

        if (is_string($establishmentId) && trim($establishmentId) !== '') {
            $ids = [trim($establishmentId)];
        } else {
            $ids = PaytimeEstablishment::query()
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id): string => (string) $id)
                ->all();
        }

        if ($ids === []) {
            $this->info('Nenhum estabelecimento disponível para sincronizar.');

            return self::SUCCESS;
        }

        $this->info('Sincronizando snapshot de planos e tarifas...');

        $updated = 0;

        foreach ($ids as $id) {
            $before = PaytimeEstablishment::query()->find((int) $id)?->pricing_snapshot_hash;
            $beforeContracted = PaytimeEstablishment::query()->find((int) $id)?->contracted_plan_snapshot_hash;
            $establishment = $pricingCacheService->syncEstablishmentPricing($id);

            if ($establishment === null) {
                $this->warn("Não foi possível sincronizar o estabelecimento {$id}.");

                continue;
            }

            $pricingCacheService->syncContractedPlanPricing($id);
            $afterContracted = PaytimeEstablishment::query()->find((int) $id)?->contracted_plan_snapshot_hash;

            $updated++;

            if ($before !== null && $before !== $establishment->pricing_snapshot_hash) {
                Log::info('Snapshot de tarifas da Paytime alterado', [
                    'establishment_id' => $id,
                    'previous_hash' => $before,
                    'current_hash' => $establishment->pricing_snapshot_hash,
                    'pricing_synced_at' => $establishment->pricing_synced_at?->toDateTimeString(),
                ]);
            }

            if ($beforeContracted !== null && $beforeContracted !== $afterContracted) {
                Log::info('Snapshot do plano contratado da Paytime alterado', [
                    'establishment_id' => $id,
                    'previous_hash' => $beforeContracted,
                    'current_hash' => $afterContracted,
                ]);
            }
        }

        $this->info("Sincronização concluída. Estabelecimentos atualizados: {$updated}.");

        return self::SUCCESS;
    }
}
