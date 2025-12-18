<?php

namespace App\Helpers;

class DashHelper
{
    public static function buildDashboardMetrics(array $boletos, array $transacoes = []): array
    {
        // -----------------------------
        // Helpers
        // -----------------------------
        $toReais = fn($value) => ($value ?? 0) / 100;

        $formatMoney = fn($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');

        $formatPercent = fn($value) => number_format((float) $value, 2, ',', '.') . '%';

        $requiredStatuses = ['PAID', 'FAILED', 'REFUNDED'];

        $buildStatusCounts = function (\Illuminate\Support\Collection $items, array $requiredStatuses) {
            $counts = array_fill_keys($requiredStatuses, 0);

            $found = $items->groupBy('status')
                ->map(fn($g) => $g->count())
                ->toArray();

            foreach ($found as $status => $count) {
                if (array_key_exists($status, $counts)) {
                    $counts[$status] = $count;
                }
            }

            return $counts;
        };

        $buildStatusPercents = function (array $counts, int $totalOnPage) use ($formatPercent) {
            $percents = [];
            foreach ($counts as $status => $count) {
                $percents[$status] = $totalOnPage > 0
                    ? $formatPercent(($count / $totalOnPage) * 100)
                    : $formatPercent(0);
            }
            return $percents;
        };

        // -----------------------------
        // Transações
        // -----------------------------
        $transactions = collect($transacoes['data'] ?? []);

        $totalTransactions = $transacoes['total'] ?? 0;
        $transactionsOnPage = $transactions->count();

        $t_totalAmountCents = (int) $transactions->sum('amount');
        $t_totalOriginalAmountCents = (int) $transactions->sum('original_amount');
        $t_totalFeesCents = (int) $transactions->sum('fees');

        $t_totalAmount = $toReais($t_totalAmountCents);
        $t_totalOriginalAmount = $toReais($t_totalOriginalAmountCents);
        $t_totalFees = $toReais($t_totalFeesCents);

        $t_averageTicket = $toReais($transactions->avg('amount') ?? 0);

        $t_averageTakeRate = $transactions
            ->filter(fn($t) => ($t['original_amount'] ?? 0) > 0)
            ->avg(fn($t) => $t['fees'] / $t['original_amount']) ?? 0;

        $t_averageTakeRatePercent = $t_averageTakeRate * 100;

        $transactionsByStatus = $buildStatusCounts($transactions, $requiredStatuses);
        $transactionsByStatusPercent = $buildStatusPercents($transactionsByStatus, $transactionsOnPage);

        $transactionsByType = $transactions
            ->groupBy('type')
            ->map(fn($group) => $group->count())
            ->toArray();

        $amountByTypeCents = $transactions
            ->groupBy('type')
            ->map(fn($group) => (int) $group->sum('amount'))
            ->toArray();

        $amountByTypeFormatted = [];
        $amountByTypePercentFormatted = [];

        foreach ($amountByTypeCents as $type => $amountCents) {
            $amount = $toReais($amountCents);
            $amountByTypeFormatted[$type] = $formatMoney($amount);

            $percent = $t_totalAmountCents > 0
                ? ($amountCents / $t_totalAmountCents) * 100
                : 0;

            $amountByTypePercentFormatted[$type] = $formatPercent($percent);
        }

        // -----------------------------
        // Boletos
        // -----------------------------
        $billets = collect($boletos['data'] ?? []);

        $totalBillets = $boletos['total'] ?? 0;
        $billetsOnPage = $billets->count();

        $b_totalAmountCents = (int) $billets->sum('amount');
        $b_totalOriginalAmountCents = (int) $billets->sum('original_amount');
        $b_totalFeesCents = (int) $billets->sum('fees');

        // Total efetivamente pago (somente PAID) usando payment_amount (se existir), senão original_amount
        $b_totalPaidCents = (int) $billets
            ->filter(fn($b) => ($b['status'] ?? null) === 'PAID')
            ->sum(fn($b) => (int) ($b['payment_amount'] ?? $b['original_amount'] ?? 0));

        $b_totalAmount = $toReais($b_totalAmountCents);
        $b_totalOriginalAmount = $toReais($b_totalOriginalAmountCents);
        $b_totalFees = $toReais($b_totalFeesCents);
        $b_totalPaid = $toReais($b_totalPaidCents);

        $billetsByStatus = $buildStatusCounts($billets, $requiredStatuses);
        $billetsByStatusPercent = $buildStatusPercents($billetsByStatus, $billetsOnPage);

        // -----------------------------
        // Totais combinados (Transações + Boletos)
        // -----------------------------
        $c_totalAmountCents = $t_totalAmountCents + $b_totalAmountCents;
        $c_totalOriginalAmountCents = $t_totalOriginalAmountCents + $b_totalOriginalAmountCents;
        $c_totalFeesCents = $t_totalFeesCents + $b_totalFeesCents;

        $c_totalAmount = $toReais($c_totalAmountCents);
        $c_totalOriginalAmount = $toReais($c_totalOriginalAmountCents);
        $c_totalFees = $toReais($c_totalFeesCents);

        // -----------------------------
        // Retorno (mantém compatibilidade com sua view)
        // - As chaves "principais" continuam representando TRANSACOES
        // - Adicionamos chaves "billets_*" e "combined_*"
        // -----------------------------
        return [
            /* =========================
             * 1) (Transações) — mantém suas chaves atuais
             * ========================= */
            /* linha 1 */
            'total_amount_formatted' => $formatMoney($t_totalAmount),
            'total_fees_formatted' => $formatMoney($t_totalFees),
            'total_original_amount_formatted' => $formatMoney($t_totalOriginalAmount),

            /* linha 2 */
            'total_transactions' => $totalTransactions,
            'average_ticket_formatted' => $formatMoney($t_averageTicket),
            'average_installments' => $transactions->avg('installments') ?? 0,

            /* linha 3 */
            'amount_by_type_formatted' => $amountByTypeFormatted,

            /* linha 4 */
            'amount_by_type_percent_formatted' => $amountByTypePercentFormatted,

            /* linha 5 */
            'transactions_by_type' => $transactionsByType,

            /* linha 6 */
            'transactions_by_status' => $transactionsByStatus,

            /* linha 7 */
            'transactions_by_status_percent' => $transactionsByStatusPercent,

            /* (extra – se quiser usar depois) */
            'average_take_rate_formatted' => $formatPercent($t_averageTakeRatePercent),

            /* =========================
             * 2) Boletos — novas métricas
             * ========================= */
            'billets_total' => $totalBillets,

            'billets_total_amount_formatted' => $formatMoney($b_totalAmount),
            'billets_total_original_amount_formatted' => $formatMoney($b_totalOriginalAmount),
            'billets_total_fees_formatted' => $formatMoney($b_totalFees),

            // Total efetivamente pago em boletos (somente PAID)
            'billets_total_paid_formatted' => $formatMoney($b_totalPaid),

            'billets_by_status' => $billetsByStatus,
            'billets_by_status_percent' => $billetsByStatusPercent,

            /* =========================
             * 3) Consolidado — transações + boletos
             * ========================= */
            'combined_total_amount_formatted' => $formatMoney($c_totalAmount),
            'combined_total_original_amount_formatted' => $formatMoney($c_totalOriginalAmount),
            'combined_total_fees_formatted' => $formatMoney($c_totalFees),
        ];
    }
}
