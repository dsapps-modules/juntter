<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Models\LinkPagamento;
use App\Models\PaytimeTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $transactionsQuery = PaytimeTransaction::query()
            ->with('establishment:id,fantasy_name,first_name,last_name');

        $linksQuery = LinkPagamento::query();

        if ($isRestricted) {
            $transactionsQuery->where('establishment_id', (string) $establishmentId);
            $linksQuery->where('estabelecimento_id', (string) $establishmentId);
        }

        $transactions = $transactionsQuery->orderByDesc('created_at')->limit(24)->get();
        $links = $linksQuery->orderByDesc('created_at')->limit(8)->get();

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
                'id' => $transaction->id,
                'type' => $this->formatType($transaction->type),
                'status' => $this->formatStatus($transaction->status),
                'amount' => $this->formatMoney((int) $transaction->amount),
                'fee' => $this->formatMoney((int) $transaction->fees),
                'customer' => $transaction->customer_name ?? 'Cliente',
                'establishment' => $establishmentName ?? 'Estabelecimento',
                'created_at' => Carbon::parse($transaction->created_at)->format('d/m/Y H:i'),
                'raw_status' => $transaction->status,
            ];
        });

        $selected = $rows->first() ?? [
            'id' => null,
            'type' => 'Sem dados',
            'status' => 'Sem dados',
            'amount' => $this->formatMoney(0),
            'fee' => $this->formatMoney(0),
            'customer' => 'Sem cliente',
            'establishment' => 'Sem estabelecimento',
            'created_at' => 'Sem atividade',
            'raw_status' => 'N/A',
        ];

        $recentLinks = $links->map(function (LinkPagamento $link): array {
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
            'summary' => $summary,
            'filters' => ['Todos', 'Pagas', 'Pendentes', 'Falhas'],
            'actions' => [
                ['title' => 'Novo cartão', 'description' => 'Fluxo de crédito e débito.', 'href' => '/cobranca'],
                ['title' => 'Novo PIX', 'description' => 'Cobrança instantânea.', 'href' => '/cobranca'],
                ['title' => 'Novo boleto', 'description' => 'Emissão e acompanhamento.', 'href' => '/cobranca'],
            ],
            'rows' => $rows->values(),
            'selected' => $selected,
            'recent_links' => $recentLinks->values(),
        ]);
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
