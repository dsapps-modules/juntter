<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Services\EstabelecimentoService;
use App\Services\TransacaoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Throwable;

class CobrancaPlanoContratadoController extends Controller
{
    public function __construct(
        private readonly EstabelecimentoService $estabelecimentoService,
        private readonly TransacaoService $transacaoService,
    ) {}

    public function __invoke(Request $request, ?int $planoId = null): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('vendedor');

        $sellerName = trim((string) $user->name) !== '' ? $user->name : 'Vendedor';
        $estabelecimentoId = $user->getEstabelecimentoId();

        if ($estabelecimentoId === null) {
            return response()->json([
                'message' => 'Nenhum estabelecimento foi vinculado ao usuário autenticado.',
                'seller_name' => $sellerName,
                'establishment' => null,
                'plan' => null,
                'actions' => $this->buildActions(),
            ]);
        }

        try {
            $estabelecimento = $this->estabelecimentoService->buscarEstabelecimento((string) $estabelecimentoId);
        } catch (Throwable) {
            return response()->json([
                'message' => 'Não foi possível carregar os dados do estabelecimento.',
                'seller_name' => $sellerName,
                'establishment' => null,
                'plan' => null,
                'actions' => $this->buildActions(),
            ]);
        }

        if (! is_array($estabelecimento)) {
            return response()->json([
                'message' => 'Dados do estabelecimento não encontrados.',
                'seller_name' => $sellerName,
                'establishment' => null,
                'plan' => null,
                'actions' => $this->buildActions(),
            ]);
        }

        $plans = collect(data_get($estabelecimento, 'plans', []));
        $selectedPlan = $this->resolveSelectedPlan($plans, $planoId);
        $plan = $this->resolvePlanDetails($selectedPlan, $planoId);

        if ($plan === null) {
            return response()->json([
                'message' => 'Nenhum plano comercial contratado foi localizado.',
                'seller_name' => $sellerName,
                'establishment' => [
                    'id' => (string) $estabelecimentoId,
                    'name' => $this->resolveEstablishmentName($estabelecimento),
                    'plans_count' => $plans->count(),
                    'has_active_plan' => (bool) data_get($selectedPlan, 'active', false),
                ],
                'plan' => null,
                'actions' => $this->buildActions(),
            ]);
        }

        return response()->json([
            'seller_name' => $sellerName,
            'establishment' => [
                'id' => (string) $estabelecimentoId,
                'name' => $this->resolveEstablishmentName($estabelecimento),
                'plans_count' => $plans->count(),
                'has_active_plan' => (bool) data_get($selectedPlan, 'active', false),
            ],
            'plan' => $plan,
            'actions' => $this->buildActions($plan),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>  $plans
     * @return array<string, mixed>|null
     */
    private function resolveSelectedPlan(Collection $plans, ?int $planoId): ?array
    {
        if ($plans->isEmpty()) {
            return null;
        }

        if ($planoId !== null) {
            $matchedPlan = $plans->first(function ($plan) use ($planoId): bool {
                return (int) data_get($plan, 'id') === $planoId;
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
     * @param  array<string, mixed>|null  $selectedPlan
     * @return array<string, mixed>|null
     */
    private function resolvePlanDetails(?array $selectedPlan, ?int $planoId): ?array
    {
        if ($selectedPlan === null && $planoId === null) {
            return null;
        }

        $planId = (int) data_get($selectedPlan, 'id', $planoId ?? 0);

        if ($planId <= 0) {
            return null;
        }

        try {
            $planDetails = $this->transacaoService->detalhesPlanoComercial($planId);
        } catch (Throwable) {
            $planDetails = $selectedPlan;
        }

        if (! is_array($planDetails)) {
            return null;
        }

        $active = (bool) data_get($planDetails, 'active', false);
        $allowAnticipation = (bool) data_get($planDetails, 'allow_anticipation', false);
        $modality = data_get($planDetails, 'modality', 'N/A');

        return [
            'id' => (int) data_get($planDetails, 'id', $planId),
            'name' => data_get($planDetails, 'name', 'Plano Comercial'),
            'description' => data_get($planDetails, 'description'),
            'gateway_id' => data_get($planDetails, 'gateway_id', 'N/A'),
            'type' => data_get($planDetails, 'type', 'N/A'),
            'modality' => $modality,
            'modality_label' => $modality === 'ONLINE' ? 'Online' : 'Presencial',
            'active' => $active,
            'status_label' => $active ? 'Ativo' : 'Inativo',
            'allow_anticipation' => $allowAnticipation,
            'allow_anticipation_label' => $allowAnticipation ? 'Sim' : 'Não',
            'contracted_at' => data_get($planDetails, 'created_at') ? Carbon::parse(data_get($planDetails, 'created_at'))->format('d/m/Y H:i') : null,
            'detail_href' => '/cobranca/planos/'.$planId,
            'back_href' => '/cobranca',
            'categories' => data_get($planDetails, 'categories', []),
            'flags' => data_get($planDetails, 'flags', []),
        ];
    }

    /**
     * @return array<int, array{label: string, href: string}>
     */
    private function buildActions(?array $plan = null): array
    {
        $actions = [
            [
                'label' => 'Voltar para cobranças',
                'href' => '/cobranca',
            ],
        ];

        if ($plan !== null) {
            $actions[] = [
                'label' => 'Ver detalhes do plano',
                'href' => $plan['detail_href'] ?? '/cobranca',
            ];
        }

        return $actions;
    }

    /**
     * @param  array<string, mixed>  $estabelecimento
     */
    private function resolveEstablishmentName(array $estabelecimento): string
    {
        return (string) data_get(
            $estabelecimento,
            'fantasy_name',
            data_get($estabelecimento, 'display_name', data_get($estabelecimento, 'name', 'Estabelecimento'))
        );
    }
}
