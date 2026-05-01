<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Models\LinkPagamento;
use App\Models\PaytimeTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CobrancaOverviewController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('vendedor');

        $establishmentId = $user->getEstabelecimentoId();
        $isRestricted = $user->isVendedor() && $establishmentId !== null;
        $selectedPeriod = $this->resolveSelectedPeriod($request->string('period')->toString());

        $transactionsQuery = PaytimeTransaction::query()
            ->with('establishment:id,fantasy_name,first_name,last_name');

        $linksQuery = LinkPagamento::query();

        if ($isRestricted) {
            $transactionsQuery->where('establishment_id', (string) $establishmentId);
            $linksQuery->where('estabelecimento_id', (string) $establishmentId);
        }

        $periods = $this->buildPeriodOptions(
            (clone $transactionsQuery)->get(['created_at'])
                ->merge((clone $linksQuery)->get(['created_at']))
        );
        $transactions = $this->applyPeriodFilter($transactionsQuery, $selectedPeriod)
            ->orderByDesc('created_at')
            ->get();
        $links = $this->applyPeriodFilter($linksQuery, $selectedPeriod)
            ->orderByDesc('created_at')
            ->get();

        $today = Carbon::today();

        $summary = [
            'total_transactions' => $transactions->count(),
            'today_transactions' => $transactions->filter(function (PaytimeTransaction $transaction) use ($today): bool {
                return Carbon::parse($transaction->created_at)->isSameDay($today);
            })->count(),
            'paid_transactions' => $transactions->where('status', 'PAID')->count(),
            'pending_transactions' => $transactions->whereIn('status', ['PENDING', 'APPROVED', 'PROCESSING'])->count(),
            'pix_transactions' => $transactions->where('type', 'PIX')->count(),
            'credit_transactions' => $transactions->where('type', 'CREDIT')->count(),
            'billet_transactions' => $transactions->where('type', 'BILLET')->count(),
            'total_amount' => $this->formatMoney((int) $transactions->sum('amount')),
            'total_fees' => $this->formatMoney((int) $transactions->sum('fees')),
            'active_links' => $links->where('status', 'ATIVO')->count(),
            'expired_links' => $links->where('status', 'EXPIRADO')->count(),
        ];

        $rows = $transactions->map(function (PaytimeTransaction $transaction) use ($user): array {
            $establishmentName = $transaction->establishment?->display_name;

            if ($establishmentName === null && $user->vendedor && $user->vendedor->estabelecimento_id !== null) {
                $establishmentName = $user->vendedor->estabelecimento_id;
            }

            return [
                'kind' => 'transaction',
                'id' => $transaction->id,
                'code' => $transaction->external_id,
                'type' => $this->formatType($transaction->type),
                'title' => $transaction->customer_name ?? 'Cliente',
                'description' => $establishmentName ?? 'Transação Pix',
                'status' => $this->formatStatus($transaction->status),
                'amount' => $this->formatMoney((int) $transaction->amount),
                'fee' => $this->formatMoney((int) $transaction->fees),
                'customer' => $transaction->customer_name ?? 'Cliente',
                'establishment' => $establishmentName ?? 'Estabelecimento',
                'created_at_sort' => Carbon::parse($transaction->created_at)->getTimestamp(),
                'created_at' => Carbon::parse($transaction->created_at)->format('d/m/Y H:i'),
                'raw_status' => $transaction->status,
            ];
        })->values();

        $linkRows = $links->map(function (LinkPagamento $link): array {
            return [
                'kind' => 'link',
                'id' => $link->id,
                'code' => $link->codigo_unico,
                'title' => $link->titulo ?? $link->descricao ?? 'Link de pagamento PIX',
                'description' => $link->descricao ?? 'Link de pagamento PIX',
                'status' => $this->formatLinkStatus($link->status),
                'amount' => $link->valor_formatado,
                'fee' => 'R$ 0,00',
                'created_at_sort' => Carbon::parse($link->created_at)->getTimestamp(),
                'created_at' => Carbon::parse($link->created_at)->format('d/m/Y H:i'),
                'raw_status' => $link->status,
                'detail_href' => '/links-pagamento-pix/'.$link->id,
                'delete_href' => '/links-pagamento-pix/'.$link->id,
            ];
        })->values();

        $selected = $rows->first() ?? [
            'id' => null,
            'kind' => 'transaction',
            'code' => null,
            'type' => 'Sem dados',
            'title' => 'Sem dados',
            'description' => 'Nenhuma movimentação disponível.',
            'status' => 'Sem dados',
            'amount' => $this->formatMoney(0),
            'fee' => $this->formatMoney(0),
            'customer' => 'Sem cliente',
            'establishment' => 'Sem estabelecimento',
            'created_at_sort' => 0,
            'created_at' => 'Sem atividade',
            'raw_status' => 'N/A',
        ];

        $recentLinks = $links->take(5)->map(function (LinkPagamento $link): array {
            return [
                'id' => $link->id,
                'title' => $link->titulo ?? $link->descricao ?? 'Link de pagamento',
                'status' => $this->formatLinkStatus($link->status),
                'amount' => $link->valor_formatado,
                'type' => $this->formatLinkType($link->tipo_pagamento ?? 'CARTAO'),
                'expires_at' => $link->data_expiracao?->format('d/m/Y H:i') ?? 'Sem expiração',
                'code' => $link->codigo_unico,
            ];
        });

        return response()->json([
            'seller_name' => trim((string) $user->name) !== '' ? $user->name : 'Vendedor',
            'summary' => $summary,
            'periods' => $periods,
            'selected_period' => $selectedPeriod,
            'filters' => ['Todos', 'Pagas', 'Pendentes', 'Falhas'],
            'actions' => [
                ['title' => 'Novo cartão', 'description' => 'Fluxo de crédito e débito.', 'href' => '/cobranca'],
                ['title' => 'Novo PIX', 'description' => 'Cobrança instantânea.', 'href' => '/cobranca'],
                ['title' => 'Novo boleto', 'description' => 'Emissão e acompanhamento.', 'href' => '/cobranca'],
            ],
            'rows' => $rows->values(),
            'link_rows' => $linkRows,
            'selected' => $selected,
            'recent_links' => $recentLinks->values(),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\PaytimeTransaction>  $transactions
     * @return array<int, array{label: string, value: string}>
     */
    private function buildPeriodOptions(Collection $transactions): array
    {
        $currentPeriod = Carbon::now()->format('Y-m');

        $availablePeriods = $transactions
            ->map(function ($transaction): string {
                return Carbon::parse($transaction->created_at)->format('Y-m');
            })
            ->unique()
            ->sortDesc()
            ->values();

        $periodValues = collect([$currentPeriod])
            ->merge($availablePeriods)
            ->unique()
            ->values();

        return $periodValues
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

        return $query->whereBetween('created_at', [
            $period->copy()->startOfMonth(),
            $period->copy()->endOfMonth(),
        ]);
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

    private function formatType(?string $type): string
    {
        return match ($type) {
            'CREDIT' => 'Credito',
            'DEBIT' => 'Debito',
            'PIX' => 'PIX',
            'BILLET' => 'Boleto',
            default => 'Transacao',
        };
    }

    private function formatStatus(?string $status): string
    {
        return match ($status) {
            'PAID' => 'Pago',
            'APPROVED' => 'Aprovado',
            'PENDING' => 'Pendente',
            'PROCESSING' => 'Processando',
            'FAILED' => 'Falha',
            'CANCELED' => 'Cancelado',
            'REFUNDED' => 'Estornado',
            default => $status ?? 'Desconhecido',
        };
    }

    private function formatLinkStatus(?string $status): string
    {
        return match ($status) {
            'ATIVO' => 'Ativo',
            'INATIVO' => 'Inativo',
            'EXPIRADO' => 'Expirado',
            'PAID' => 'Pago',
            default => $status ?? 'Desconhecido',
        };
    }

    private function formatLinkType(?string $type): string
    {
        return match ($type) {
            'PIX' => 'PIX',
            'BOLETO' => 'Boleto',
            'CARTAO' => 'Cartão',
            default => 'Cartão',
        };
    }
}
