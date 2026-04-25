<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Models\PaytimeEstablishment;
use App\Models\PaytimeTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardOverviewController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('vendedor');

        $month = $this->normalizeMonth((int) $request->input('mes', now()->month));
        $year = $this->normalizeYear((int) $request->input('ano', now()->year));
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = (clone $periodStart)->endOfMonth();

        $establishmentsQuery = PaytimeEstablishment::query()->orderByDesc('updated_at');

        if ($user->isVendedor() && $user->getEstabelecimentoId()) {
            $establishmentsQuery->whereKey($user->getEstabelecimentoId());
        }

        $establishments = $establishmentsQuery->limit(12)->get();
        $establishmentIds = $establishments->pluck('id')->all();

        $transactionsQuery = PaytimeTransaction::query()
            ->with('establishment:id,fantasy_name,first_name,last_name')
            ->whereBetween('created_at', [$periodStart, $periodEnd]);

        if ($establishmentIds !== []) {
            $transactionsQuery->whereIn('establishment_id', $establishmentIds);
        } else {
            $transactionsQuery->whereRaw('1 = 0');
        }

        $totalTransactions = (clone $transactionsQuery)->count();
        $grossAmount = (int) (clone $transactionsQuery)->sum('original_amount');
        $netAmount = (int) (clone $transactionsQuery)->sum('amount');
        $totalFees = max(0, $grossAmount - $netAmount);
        $averageTicket = $totalTransactions > 0 ? (int) round($netAmount / $totalTransactions) : 0;

        $transactionsByType = (clone $transactionsQuery)
            ->selectRaw('type, COUNT(*) as total, SUM(amount) as amount, SUM(original_amount) as original_amount')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $transactionsByStatus = (clone $transactionsQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $summary = [
            'total_establishments' => $establishments->count(),
            'active_establishments' => $establishments->where('active', true)->count(),
            'blocked_establishments' => $establishments->whereIn('status', ['BLOCKED', 'SUSPENDED'])->count(),
            'total_transactions' => $totalTransactions,
            'pending_transactions' => (clone $transactionsQuery)->whereIn('status', ['PENDING', 'APPROVED', 'PROCESSING'])->count(),
            'today_transactions' => (clone $transactionsQuery)->whereDate('created_at', Carbon::today())->count(),
            'total_revenue' => $this->formatMoney($netAmount),
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

        $overviewCards = [
            [
                'key' => 'net_revenue',
                'value' => $this->formatMoney($netAmount),
                'label' => 'Faturamento Líquido',
                'tone' => 'blue',
                'icon' => 'wallet',
            ],
            [
                'key' => 'gross_revenue',
                'value' => $this->formatMoney($grossAmount),
                'label' => 'Faturamento Bruto',
                'tone' => 'cyan',
                'icon' => 'clock',
            ],
            [
                'key' => 'fees',
                'value' => $this->formatMoney($totalFees),
                'label' => 'Descontos / Taxas',
                'tone' => 'amber',
                'icon' => 'fees',
            ],
            [
                'key' => 'transactions',
                'value' => number_format($totalTransactions, 0, ',', '.'),
                'label' => 'Total de Transações',
                'tone' => 'slate',
                'icon' => 'receipt',
            ],
            [
                'key' => 'average_ticket',
                'value' => $this->formatMoney($averageTicket),
                'label' => 'Ticket Médio',
                'tone' => 'green',
                'icon' => 'ticket',
            ],
            [
                'key' => 'balance',
                'value' => 'Consultar Extrato',
                'label' => 'Saldo em Conta',
                'tone' => 'dark',
                'icon' => 'layers',
            ],
        ];

        $distributionSections = [
            [
                'key' => 'CREDIT',
                'label' => 'Cartão de Crédito',
                'tone' => 'blue',
                'icon' => 'card',
                'cards' => $this->paymentMethodCards($transactionsByType->get('CREDIT'), $netAmount, 'Cartão de Crédito'),
            ],
            [
                'key' => 'DEBIT',
                'label' => 'Cartão de Débito',
                'tone' => 'green',
                'icon' => 'bank',
                'cards' => $this->paymentMethodCards($transactionsByType->get('DEBIT'), $netAmount, 'Cartão de Débito'),
            ],
            [
                'key' => 'PIX',
                'label' => 'Pix',
                'tone' => 'cyan',
                'icon' => 'bolt',
                'cards' => $this->paymentMethodCards($transactionsByType->get('PIX'), $netAmount, 'Pix'),
            ],
            [
                'key' => 'BILLET',
                'label' => 'Boleto',
                'tone' => 'amber',
                'icon' => 'document',
                'cards' => $this->paymentMethodCards($transactionsByType->get('BILLET'), $grossAmount, 'Boleto'),
            ],
        ];

        $statusSections = $this->statusSections($transactionsByStatus, $totalTransactions);

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
            'period' => [
                'month' => $month,
                'year' => $year,
                'label' => $this->periodLabel($periodStart),
            ],
            'overview_cards' => $overviewCards,
            'distribution_sections' => $distributionSections,
            'status_sections' => $statusSections,
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

    /**
     * @return array<int, array{kind: string, value: string, label: string}>
     */
    private function paymentMethodCards(?object $stats, int $baseAmount, string $label): array
    {
        $amount = (int) ($stats?->amount ?? 0);
        $total = (int) ($stats?->total ?? 0);

        return [
            [
                'kind' => 'amount',
                'value' => $this->formatMoney($amount),
                'label' => $label,
            ],
            [
                'kind' => 'percent',
                'value' => $baseAmount > 0 ? number_format(($amount / $baseAmount) * 100, 2, ',', '.').'%' : '0,00%',
                'label' => $label,
            ],
            [
                'kind' => 'count',
                'value' => number_format($total, 0, ',', '.'),
                'label' => $label,
            ],
        ];
    }

    /**
     * @return array<int, array{key: string, cards: array<int, array{kind: string, value: string, label: string, tone: string}>}>
     */
    private function statusSections(Collection $transactionsByStatus, int $baseTotal): array
    {
        $definitions = [
            ['key' => 'PAID', 'label' => 'Pagamento Efetivado', 'tone' => 'success'],
            ['key' => 'FAILED', 'label' => 'Pagamento Cancelado', 'tone' => 'danger'],
            ['key' => 'REFUNDED', 'label' => 'Pagamento Devolvido', 'tone' => 'warning'],
        ];

        $countRow = [];
        $percentRow = [];

        foreach ($definitions as $definition) {
            $status = $transactionsByStatus->get($definition['key']);
            $count = (int) ($status->total ?? 0);

            $countRow[] = [
                'kind' => 'count',
                'value' => number_format($count, 0, ',', '.'),
                'label' => $definition['label'],
                'tone' => $definition['tone'],
            ];

            $percentRow[] = [
                'kind' => 'percent',
                'value' => $baseTotal > 0 ? number_format(($count / $baseTotal) * 100, 2, ',', '.').'%' : '0,00%',
                'label' => $definition['label'],
                'tone' => $definition['tone'],
            ];
        }

        return [
            [
                'key' => 'status_counts',
                'cards' => $countRow,
            ],
            [
                'key' => 'status_percentages',
                'cards' => $percentRow,
            ],
        ];
    }

    private function normalizeMonth(int $month): int
    {
        return max(1, min(12, $month));
    }

    private function normalizeYear(int $year): int
    {
        return max(2000, $year);
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

    private function periodLabel(Carbon $periodStart): string
    {
        $months = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];

        return ($months[(int) $periodStart->format('n')] ?? 'Mês').' '.$periodStart->format('Y');
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
