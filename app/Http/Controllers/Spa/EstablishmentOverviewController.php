<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Models\PaytimeEstablishment;
use App\Models\PaytimeTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class EstablishmentOverviewController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $establishments = PaytimeEstablishment::query()
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get();

        if ($establishments->isEmpty()) {
            return response()->json([
                'summary' => [
                    'total_establishments' => 0,
                    'active_establishments' => 0,
                    'blocked_establishments' => 0,
                    'total_revenue' => $this->formatMoney(0),
                ],
                'filters' => ['Todos', 'Ativos', 'Inadimplentes', 'Inativos'],
                'rows' => [],
                'selected' => [
                    'id' => null,
                    'name' => 'Sem dados',
                    'status' => 'Em análise',
                    'email' => 'N/A',
                    'revenue' => $this->formatMoney(0),
                    'active_tasks' => 0,
                    'segment' => 'N/A',
                    'owner' => 'N/A',
                    'phone' => 'N/A',
                    'city' => 'N/A',
                    'timeline' => [],
                ],
                'recent_transactions' => [],
            ]);
        }

        $establishmentIds = $establishments->pluck('id')->all();

        $transactionStats = PaytimeTransaction::query()
            ->selectRaw('establishment_id, COUNT(*) as total_transactions, SUM(amount) as total_amount, MAX(created_at) as last_activity')
            ->whereIn('establishment_id', $establishmentIds)
            ->groupBy('establishment_id')
            ->get()
            ->keyBy('establishment_id');

        $recentTransactions = PaytimeTransaction::query()
            ->with('establishment:id,fantasy_name,first_name,last_name')
            ->whereIn('establishment_id', $establishmentIds)
            ->orderByDesc('created_at')
            ->limit(24)
            ->get();

        $transactionsByEstablishment = $recentTransactions->groupBy('establishment_id');

        $rows = $establishments->map(function (PaytimeEstablishment $establishment) use ($transactionStats, $transactionsByEstablishment): array {
            $stats = $transactionStats->get($establishment->id);
            $revenue = (int) ($establishment->revenue * 100);
            $totalTransactions = (int) ($stats->total_transactions ?? 0);
            $status = $this->mapStatus($establishment);
            $timeline = $transactionsByEstablishment->get($establishment->id, collect())
                ->take(3)
                ->map(function (PaytimeTransaction $transaction): array {
                    return [
                        'color' => 'gold',
                        'title' => $this->formatType($transaction->type),
                        'description' => Carbon::parse($transaction->created_at)->format('d/m/Y H:i'),
                    ];
                })
                ->values()
                ->all();

            return [
                'id' => $establishment->id,
                'name' => $establishment->display_name,
                'initials' => $this->makeInitials($establishment->display_name),
                'status' => $status,
                'email' => $establishment->email,
                'revenue' => $this->formatMoney($revenue),
                'revenue_cents' => $revenue,
                'active_tasks' => max(1, $totalTransactions),
                'updated_at' => $stats?->last_activity ? Carbon::parse($stats->last_activity)->format('d/m/Y H:i') : 'Sem atividade',
                'segment' => $establishment->category ?? 'Geral',
                'owner' => trim(($establishment->responsible_json['name'] ?? '') ?: $establishment->display_name),
                'phone' => $establishment->phone_number ?? 'N/A',
                'city' => $establishment->address_json['city'] ?? 'N/A',
                'timeline' => $timeline,
            ];
        });

        $detail = $rows->first() ?? [
            'id' => null,
            'name' => 'Sem dados',
            'status' => 'Em análise',
            'email' => 'N/A',
            'revenue' => $this->formatMoney(0),
            'active_tasks' => 0,
            'segment' => 'N/A',
            'owner' => 'N/A',
            'phone' => 'N/A',
            'city' => 'N/A',
            'timeline' => [],
        ];

        return response()->json([
            'summary' => [
                'total_establishments' => $establishments->count(),
                'active_establishments' => $establishments->where('active', true)->count(),
                'blocked_establishments' => $establishments->where('status', 'BLOCKED')->count(),
                'total_revenue' => $this->formatMoney((int) round(((float) $establishments->sum('revenue')) * 100)),
            ],
            'filters' => ['Todos', 'Ativos', 'Inadimplentes', 'Inativos'],
            'rows' => $rows->values(),
            'selected' => array_merge($detail, [
                'timeline' => $detail['timeline'] ?? [],
            ]),
            'recent_transactions' => $recentTransactions->map(function (PaytimeTransaction $transaction): array {
                return [
                    'id' => $transaction->id,
                    'establishment' => $transaction->establishment?->display_name ?? 'Estabelecimento',
                    'amount' => $this->formatMoney((int) $transaction->amount),
                    'status' => $transaction->status,
                    'type' => $this->formatType($transaction->type),
                    'created_at' => Carbon::parse($transaction->created_at)->format('d/m/Y H:i'),
                ];
            })->values(),
        ]);
    }

    private function mapStatus(PaytimeEstablishment $establishment): string
    {
        if (! $establishment->active) {
            return 'Inativo';
        }

        return match ($establishment->status) {
            'BLOCKED', 'SUSPENDED' => 'Bloqueado',
            'REVIEW' => 'Em análise',
            default => 'Ativo',
        };
    }

    private function makeInitials(string $value): string
    {
        $parts = preg_split('/\s+/', trim($value)) ?: [];
        $initials = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'JT';
    }

    private function formatMoney(int $amountInCents): string
    {
        return 'R$ '.number_format($amountInCents / 100, 2, ',', '.');
    }

    private function formatType(?string $type): string
    {
        return match ($type) {
            'CREDIT' => 'Crédito',
            'DEBIT' => 'Débito',
            'PIX' => 'PIX',
            'BILLET' => 'Boleto',
            default => 'Transação',
        };
    }
}
