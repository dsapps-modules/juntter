<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Models\LinkPagamento;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LinksPagamentoOverviewController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $establishmentId = $user->getEstabelecimentoId();
        $selectedPeriod = $this->resolveSelectedPeriod($request->string('period')->toString());

        $linksQuery = LinkPagamento::query();

        if ($establishmentId !== null) {
            $linksQuery->where('estabelecimento_id', (string) $establishmentId);
        } else {
            $linksQuery->whereRaw('1 = 0');
        }

        $periods = $this->buildPeriodOptions((clone $linksQuery)->get(['created_at']));
        $links = $this->applyPeriodFilter($linksQuery, $selectedPeriod)
            ->orderByDesc('created_at')
            ->get();

        $summary = [
            'total_links' => $links->count(),
            'active_links' => $links->where('status', 'ATIVO')->count(),
            'inactive_links' => $links->where('status', 'INATIVO')->count(),
            'expired_links' => $links->where('status', 'EXPIRADO')->count(),
            'paid_links' => $links->where('status', 'PAID')->count(),
            'card_links' => $links->where('tipo_pagamento', 'CARTAO')->count(),
            'pix_links' => $links->where('tipo_pagamento', 'PIX')->count(),
            'boleto_links' => $links->where('tipo_pagamento', 'BOLETO')->count(),
            'total_value' => $this->formatMoney((int) round($links->sum('valor_centavos'))),
        ];

        $rows = $links->map(function (LinkPagamento $link): array {
            return [
                'id' => $link->id,
                'code' => $link->codigo_unico,
                'title' => $link->titulo ?? $link->descricao ?? 'Link de pagamento',
                'description' => $link->descricao ?? 'Sem descrição',
                'status' => $this->formatStatus($link->status),
                'type' => $this->formatType($link->tipo_pagamento),
                'raw_type' => $link->tipo_pagamento,
                'amount' => $link->valor_formatado,
                'max_installments' => $link->parcelas_maximas,
                'expires_at' => $link->data_expiracao?->format('d/m/Y H:i') ?? 'Sem expiração',
                'return_url' => $link->url_retorno,
                'webhook_url' => $link->url_webhook,
                'created_at' => Carbon::parse($link->created_at)->format('d/m/Y H:i'),
                'created_at_sort' => Carbon::parse($link->created_at)->getTimestamp(),
                'detail_href' => '/links-pagamento/'.$link->id,
            ];
        });

        $recentCardLinks = $rows
            ->filter(function (array $link): bool {
                return ($link['raw_type'] ?? null) === 'CARTAO';
            })
            ->take(2)
            ->values();

        $selected = $rows->first() ?? [
            'id' => null,
            'code' => 'N/A',
            'title' => 'Sem links',
            'description' => 'Nenhum link cadastrado.',
            'status' => 'Inativo',
            'type' => 'Cartão',
            'amount' => $this->formatMoney(0),
            'max_installments' => 1,
            'expires_at' => 'Sem expiração',
            'return_url' => null,
            'webhook_url' => null,
            'created_at' => 'Sem atividade',
        ];

        return response()->json([
            'summary' => $summary,
            'periods' => $periods,
            'selected_period' => $selectedPeriod,
            'filters' => ['Todos', 'Ativos', 'Expirados', 'Pagos'],
            'actions' => [
                ['title' => 'Novo cartão', 'description' => 'Fluxo em Cartão.', 'href' => '/links-pagamento/novo?tipo=CARTAO'],
                ['title' => 'Novo PIX', 'description' => 'Fluxo instantâneo.', 'href' => '/links-pagamento/novo?tipo=PIX'],
                ['title' => 'Novo boleto', 'description' => 'Fluxo bancário.', 'href' => '/links-pagamento/novo?tipo=BOLETO'],
            ],
            'rows' => $rows->values(),
            'selected' => $selected,
            'recent_links' => $rows->take(5)->values(),
            'recent_card_links' => $recentCardLinks,
        ]);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function buildPeriodOptions(Collection $links): array
    {
        $currentPeriod = Carbon::now()->format('Y-m');

        $availablePeriods = $links
            ->map(function ($link): string {
                return Carbon::parse($link->created_at)->format('Y-m');
            })
            ->unique()
            ->sortDesc()
            ->values();

        return collect([$currentPeriod])
            ->merge($availablePeriods)
            ->unique()
            ->map(function (string $period): array {
                return [
                    'label' => Carbon::createFromFormat('Y-m', $period)->format('m/Y'),
                    'value' => $period,
                ];
            })
            ->prepend([
                'label' => 'Todos os meses',
                'value' => 'all',
            ])
            ->values()
            ->all();
    }

    private function applyPeriodFilter(Builder $query, string $selectedPeriod): Builder
    {
        if ($selectedPeriod === 'all') {
            return $query;
        }

        $period = Carbon::createFromFormat('Y-m', $selectedPeriod);

        return $query
            ->whereYear('created_at', $period->year)
            ->whereMonth('created_at', $period->month);
    }

    private function resolveSelectedPeriod(string $selectedPeriod): string
    {
        if ($selectedPeriod === 'all') {
            return 'all';
        }

        try {
            Carbon::createFromFormat('Y-m', $selectedPeriod);
        } catch (\Throwable) {
            return Carbon::now()->format('Y-m');
        }

        return $selectedPeriod;
    }

    private function formatMoney(int $amountInCents): string
    {
        return 'R$ '.number_format($amountInCents / 100, 2, ',', '.');
    }

    private function formatStatus(?string $status): string
    {
        return match ($status) {
            'ATIVO' => 'Ativo',
            'INATIVO' => 'Inativo',
            'EXPIRADO' => 'Expirado',
            'PAID' => 'Pago',
            default => $status ?? 'Desconhecido',
        };
    }

    private function formatType(?string $type): string
    {
        return match ($type) {
            'PIX' => 'PIX',
            'BOLETO' => 'Boleto',
            default => 'Cartão',
        };
    }
}
