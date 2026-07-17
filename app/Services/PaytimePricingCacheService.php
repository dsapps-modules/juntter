<?php

namespace App\Services;

use App\Models\PaytimeEstablishment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PaytimePricingCacheService
{
    public function __construct(
        private readonly EstabelecimentoService $estabelecimentoService,
        private readonly TransacaoService $transacaoService,
    ) {}

    public function resolvePixOutFeeCents(string $establishmentId): int
    {
        $feeCents = $this->extractPixOutFeeCents($this->cachedEstablishment($establishmentId));

        if ($feeCents !== null) {
            return $feeCents;
        }

        return max(0, (int) config('services.paytime.payout_fee_cents', 100));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveContractedPlan(string $establishmentId, ?int $planId = null): ?array
    {
        $establishment = $this->cachedEstablishment($establishmentId);

        if ($establishment === null) {
            return null;
        }

        $cachedContractedPlan = $this->normalizeCachedContractedPlan($establishment->contracted_plan_json ?? null, $planId);

        if ($cachedContractedPlan !== null) {
            return $cachedContractedPlan;
        }

        $plans = collect($establishment->plans_json ?? [])
            ->filter(fn ($plan): bool => $this->isOnlinePlan($plan))
            ->values();

        if ($plans->isEmpty()) {
            return null;
        }

        $selectedPlan = $this->resolveSelectedPlan($plans, $planId);

        if ($selectedPlan === null) {
            return null;
        }

        return $this->normalizePlanDetails($selectedPlan, $planId);
    }

    public function syncEstablishmentPricing(string $establishmentId): ?PaytimeEstablishment
    {
        try {
            $payload = $this->estabelecimentoService->buscarEstabelecimento($establishmentId);
        } catch (\Throwable) {
            return $this->cachedEstablishment($establishmentId);
        }

        if (! is_array($payload)) {
            return $this->cachedEstablishment($establishmentId);
        }

        return $this->persistPricingSnapshot($payload);
    }

    public function syncContractedPlanPricing(string $establishmentId, ?int $planId = null): ?PaytimeEstablishment
    {
        $establishment = $this->cachedEstablishment($establishmentId);

        if ($establishment === null) {
            return null;
        }

        $resolvedPlanId = $this->resolveContractedPlanId($establishment, $planId);

        if ($resolvedPlanId === null) {
            return $establishment;
        }

        try {
            $payload = $this->transacaoService->detalhesPlanoComercial($resolvedPlanId);
        } catch (\Throwable) {
            return $establishment;
        }

        if (! is_array($payload) || ! $this->isOnlinePlan($payload)) {
            return $establishment;
        }

        return $this->persistContractedPlanSnapshot((string) $establishmentId, $payload);
    }

    public function persistPricingSnapshot(array $payload): ?PaytimeEstablishment
    {
        $establishmentId = $this->resolveEstablishmentId($payload);

        if ($establishmentId === null) {
            return null;
        }

        $pricingSnapshot = $this->buildPricingSnapshot($payload);

        $attributes = array_merge(
            $this->baseAttributes($payload),
            $pricingSnapshot,
            [
                'pricing_snapshot_hash' => $this->hashSnapshot($pricingSnapshot),
                'pricing_source_updated_at' => $this->parseDate(data_get($payload, 'updated_at')),
                'pricing_synced_at' => now(),
            ]
        );

        $establishment = PaytimeEstablishment::query()->updateOrCreate([
            'id' => (int) $establishmentId,
        ], $attributes);

        $this->maybeClearContractedPlanSnapshot($establishment, $payload);

        return $establishment->fresh();
    }

    public function persistContractedPlanSnapshot(string $establishmentId, array $payload): ?PaytimeEstablishment
    {
        $establishment = $this->cachedEstablishment($establishmentId);

        if ($establishment === null) {
            return null;
        }

        $planId = $this->resolvePlanId($payload);

        if ($planId === null) {
            return $establishment;
        }

        $attributes = [
            'contracted_plan_json' => $payload,
            'contracted_plan_snapshot_hash' => $this->hashSnapshot($payload),
            'contracted_plan_source_updated_at' => $this->parseDate(data_get($payload, 'updated_at')),
            'contracted_plan_synced_at' => now(),
        ];

        $establishment->forceFill($attributes)->save();

        return $establishment->fresh();
    }

    public function cachedEstablishment(string $establishmentId): ?PaytimeEstablishment
    {
        return PaytimeEstablishment::query()->find((int) $establishmentId);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseAttributes(array $payload): array
    {
        return [
            'type' => data_get($payload, 'type'),
            'first_name' => data_get($payload, 'first_name'),
            'last_name' => data_get($payload, 'last_name'),
            'fantasy_name' => data_get($payload, 'fantasy_name'),
            'document' => data_get($payload, 'document'),
            'email' => data_get($payload, 'email'),
            'phone_number' => data_get($payload, 'phone_number'),
            'active' => (bool) data_get($payload, 'active', true),
            'status' => data_get($payload, 'status'),
            'risk' => data_get($payload, 'risk'),
            'category' => data_get($payload, 'category'),
            'code' => data_get($payload, 'code'),
            'revenue' => data_get($payload, 'revenue'),
            'address_json' => data_get($payload, 'address'),
            'responsible_json' => data_get($payload, 'responsible', data_get($payload, 'representative')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPricingSnapshot(array $payload): array
    {
        return [
            'plans_json' => collect(data_get($payload, 'plans', []))->values()->all(),
            'fees_banking_json' => collect(data_get($payload, 'fees_banking', []))->values()->all(),
            'pricing_snapshot_json' => [
                'plans' => collect(data_get($payload, 'plans', []))->values()->all(),
                'fees_banking' => collect(data_get($payload, 'fees_banking', []))->values()->all(),
            ],
        ];
    }

    private function resolveContractedPlanId(PaytimeEstablishment $establishment, ?int $planId = null): ?int
    {
        if ($planId !== null) {
            return $planId;
        }

        $cachedContractedPlanId = $this->resolvePlanId($establishment->contracted_plan_json ?? null);

        if ($cachedContractedPlanId !== null) {
            return $cachedContractedPlanId;
        }

        $plans = collect($establishment->plans_json ?? [])
            ->filter(fn ($plan): bool => $this->isOnlinePlan($plan))
            ->values();

        $selectedPlan = $this->resolveSelectedPlan($plans, null);

        return $this->resolvePlanId($selectedPlan);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function resolvePlanId(?array $payload): ?int
    {
        if ($payload === null) {
            return null;
        }

        $value = data_get($payload, 'id');

        if (! is_numeric($value)) {
            return null;
        }

        $planId = (int) $value;

        return $planId > 0 ? $planId : null;
    }

    private function maybeClearContractedPlanSnapshot(PaytimeEstablishment $establishment, array $payload): void
    {
        $currentContractedPlanId = $this->resolvePlanId($establishment->contracted_plan_json ?? null);

        if ($currentContractedPlanId === null) {
            return;
        }

        $activePlanIds = collect(data_get($payload, 'plans', []))
            ->filter(fn ($plan): bool => $this->isOnlinePlan($plan))
            ->pluck('id')
            ->filter(fn ($value): bool => is_numeric($value))
            ->map(fn ($value): int => (int) $value)
            ->values()
            ->all();

        if (in_array($currentContractedPlanId, $activePlanIds, true)) {
            return;
        }

        $establishment->forceFill([
            'contracted_plan_json' => null,
            'contracted_plan_snapshot_hash' => null,
            'contracted_plan_source_updated_at' => null,
            'contracted_plan_synced_at' => null,
        ])->save();
    }

    private function resolveEstablishmentId(array $payload): ?string
    {
        $value = data_get($payload, 'id');

        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }

    private function extractPixOutFeeCents(?PaytimeEstablishment $establishment): ?int
    {
        if ($establishment === null) {
            return null;
        }

        $feesBanking = $establishment->fees_banking_json ?? [];

        foreach ($feesBanking as $feePackage) {
            $fees = data_get($feePackage, 'fees', []);

            foreach (['pix', 'dynamic_pix'] as $feeKey) {
                $value = data_get($fees, $feeKey);

                if (is_numeric($value)) {
                    return max(0, (int) $value);
                }
            }
        }

        return null;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>  $plans
     * @return array<string, mixed>|null
     */
    private function resolveSelectedPlan(Collection $plans, ?int $planId): ?array
    {
        if ($planId !== null) {
            $matchedPlan = $plans->first(function ($plan) use ($planId): bool {
                return (int) data_get($plan, 'id') === $planId;
            });

            if (is_array($matchedPlan)) {
                return $matchedPlan;
            }
        }

        $activePlan = $plans->first(function ($plan): bool {
            return (bool) data_get($plan, 'active', false);
        });

        if (is_array($activePlan)) {
            return $activePlan;
        }

        $firstPlan = $plans->first();

        return is_array($firstPlan) ? $firstPlan : null;
    }

    /**
     * @param  array<string, mixed>  $selectedPlan
     * @return array<string, mixed>
     */
    private function normalizePlanDetails(array $selectedPlan, ?int $planId): array
    {
        $planId = (int) data_get($selectedPlan, 'id', $planId ?? 0);
        $modality = (string) data_get($selectedPlan, 'modality', 'N/A');
        $active = (bool) data_get($selectedPlan, 'active', false);
        $allowAnticipation = (bool) data_get($selectedPlan, 'allow_anticipation', false);

        return [
            'id' => $planId,
            'name' => data_get($selectedPlan, 'name', 'Plano Comercial'),
            'description' => data_get($selectedPlan, 'description'),
            'gateway_id' => data_get($selectedPlan, 'gateway_id', 'N/A'),
            'type' => data_get($selectedPlan, 'type', 'N/A'),
            'modality' => $modality,
            'modality_label' => $modality === 'ONLINE' ? 'Online' : 'Presencial',
            'active' => $active,
            'status_label' => $active ? 'Ativo' : 'Inativo',
            'allow_anticipation' => $allowAnticipation,
            'allow_anticipation_label' => $allowAnticipation ? 'Sim' : 'Não',
            'contracted_at' => data_get($selectedPlan, 'created_at') ? Carbon::parse((string) data_get($selectedPlan, 'created_at'))->format('d/m/Y H:i') : null,
            'detail_href' => '/cobranca/planos/'.$planId,
            'back_href' => '/cobranca',
            'categories' => data_get($selectedPlan, 'categories', []),
            'flags' => data_get($selectedPlan, 'flags', []),
        ];
    }

    private function isOnlinePlan(mixed $plan): bool
    {
        return strtoupper((string) data_get($plan, 'modality', '')) === 'ONLINE';
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function hashSnapshot(array $snapshot): string
    {
        return sha1(json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function normalizeCachedContractedPlan(?array $payload, ?int $planId): ?array
    {
        if ($payload === null) {
            return null;
        }

        if ($planId !== null && $this->resolvePlanId($payload) !== $planId) {
            return null;
        }

        return $this->normalizePlanDetails($payload, $planId);
    }
}
