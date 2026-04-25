<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Models\PaytimeEstablishment;
use App\Models\PaytimeTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardOverviewController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('vendedor');

        $establishmentsQuery = PaytimeEstablishment::query()->orderByDesc('updated_at');

        if ($user->isVendedor() && $user->getEstabelecimentoId()) {
            $establishmentsQuery->whereKey($user->getEstabelecimentoId());
        }

        $establishments = $establishmentsQuery->limit(12)->get();
        $establishmentIds = $establishments->pluck('id')->all();

        $transactionsQuery = PaytimeTransaction::query()
            ->with('establishment:id,fantasy_name,first_name,last_name');

        if ($establishmentIds !== []) {
            $transactionsQuery->whereIn('establishment_id', $establishmentIds);
        } else {
            $transactionsQuery->whereRaw('1 = 0');
        }

        $summary = [
            'total_establishments' => $establishments->count(),
            'active_establishments' => $establishments->where('active', true)->count(),
            'blocked_establishments' => $establishments->whereIn('status', ['BLOCKED', 'SUSPENDED'])->count(),
            'total_transactions' => (clone $transactionsQuery)->count(),
            'pending_transactions' => (clone $transactionsQuery)->whereIn('status', ['PENDING', 'APPROVED', 'PROCESSING'])->count(),
            'today_transactions' => (clone $transactionsQuery)->whereDate('created_at', Carbon::today())->count(),
            'total_revenue' => $this->formatMoney((int) (clone $transactionsQuery)->sum('amount')),
        ];

        $transactionStats = (clone $transactionsQuery)
            ->selectRaw('establishment_id, COUNT(*) as total_transactions, SUM(amount) as total_amount, MAX(created_at) as last_activity')
            ->groupBy('establishment_id')
            ->get()
            ->keyBy('establishment_id');

        $recentTransactions = (clone $transactionsQuery)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $transactionsByEstablishment = $recentTransactions->groupBy('establishment_id');

        $rows = $establishments->map(function (PaytimeEstablishment $establishment) use ($transactionStats, $transactionsByEstablishment): array {
            $stats = $transactionStats->get($establishment->id);
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

            $revenueInCents = (int) round(((float) $establishment->revenue) * 100);

            return [
                'id' => $establishment->id,
                'name' => $establishment->display_name,
                'initials' => $this->makeInitials($establishment->display_name),
                'status' => $this->mapStatus($establishment),
                'email' => $establishment->email ?? 'N/A',
                'revenue' => $this->formatMoney($revenueInCents),
                'revenue_cents' => $revenueInCents,
                'active_tasks' => max(1, (int) ($stats?->total_transactions ?? 0)),
                'updated_at' => $stats?->last_activity ? Carbon::parse($stats->last_activity)->format('d/m/Y H:i') : 'Sem atividade',
                'segment' => $establishment->category ?? 'Geral',
                'owner' => trim(($establishment->responsible_json['name'] ?? '') ?: $establishment->display_name),
                'phone' => $establishment->phone_number ?? 'N/A',
                'city' => $establishment->address_json['city'] ?? 'N/A',
                'timeline' => $timeline,
            ];
        });

        $selected = $rows->first() ?? [
            'id' => null,
            'name' => 'Sem dados',
            'status' => 'Em analise',
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
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'nivel_acesso' => $user->nivel_acesso,
                'nivel_label' => $this->roleLabel($user->nivel_acesso),
                'verified' => $user->hasVerifiedEmail(),
                'must_change_password' => (bool) ($user->vendedor?->must_change_password ?? false),
                'created_at' => $user->created_at?->format('d/m/Y'),
            ],
            'summary' => $summary,
            'filters' => ['Todos', 'Ativos', 'Inadimplentes', 'Inativos'],
            'actions' => [
                ['title' => 'Estabelecimentos', 'description' => 'Cadastro e monitoramento da base.', 'href' => '/estabelecimentos'],
                ['title' => 'Cobrança', 'description' => 'Fluxos de cartão, PIX e boleto.', 'href' => '/cobranca'],
                ['title' => 'Vendedores', 'description' => 'Acessos e faturamento.', 'href' => '/vendedores'],
                ['title' => 'Perfil', 'description' => 'Dados e segurança da conta.', 'href' => '/perfil'],
            ],
            'rows' => $rows->values(),
            'selected' => $selected,
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
            'REVIEW' => 'Em analise',
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
            'CREDIT' => 'Credito',
            'DEBIT' => 'Debito',
            'PIX' => 'PIX',
            'BILLET' => 'Boleto',
            default => 'Transacao',
        };
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'super_admin' => 'Super admin',
            'admin' => 'Admin',
            'vendedor' => 'Vendedor',
            default => 'Usuario',
        };
    }
}
