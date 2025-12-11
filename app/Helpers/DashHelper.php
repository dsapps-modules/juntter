<?php

namespace App\Helpers;

class DashHelper
{
    public static function buildDashboardMetrics(array $response): array
    {
        $transactions = collect($response['data'] ?? []);

        $totalTransactions = $response['total'] ?? 0;
        $perPage = $response['perPage'] ?? 0;
        $currentPage = $response['page'] ?? 1;
        $transactionsOnPage = $transactions->count();

        $toReais = fn ($value) => ($value ?? 0) / 100;

        $formatMoney = fn ($value) => 'R$ '.number_format($value, 2, ',', '.');

        $formatPercent = fn ($value) => number_format($value, 2, ',', '.').'%';

        // Totais
        $totalAmountCents = $transactions->sum('amount');
        $totalOriginalAmountCents = $transactions->sum('original_amount');
        $totalFeesCents = $transactions->sum('fees');

        $totalAmount = $toReais($totalAmountCents);
        $totalOriginalAmount = $toReais($totalOriginalAmountCents);
        $totalFees = $toReais($totalFeesCents);

        // Médias
        $averageTicket = $toReais($transactions->avg('amount') ?? 0);

        $averageTakeRate = $transactions
            ->filter(fn ($t) => ($t['original_amount'] ?? 0) > 0)
            ->avg(fn ($t) => $t['fees'] / $t['original_amount']) ?? 0;

        $averageTakeRatePercent = $averageTakeRate * 100;

        // ✅ STATUS PADRONIZADOS
        $requiredStatuses = ['PAID', 'FAILED', 'REFUNDED'];

        $transactionsByStatus = array_fill_keys($requiredStatuses, 0);

        $foundStatuses = $transactions
            ->groupBy('status')
            ->map(fn ($group) => $group->count())
            ->toArray();

        foreach ($foundStatuses as $status => $count) {
            if (array_key_exists($status, $transactionsByStatus)) {
                $transactionsByStatus[$status] = $count;
            }
        }

        $transactionsByStatusPercent = [];
        foreach ($transactionsByStatus as $status => $count) {
            $transactionsByStatusPercent[$status] =
                $transactionsOnPage > 0
                    ? $formatPercent(($count / $transactionsOnPage) * 100)
                    : 0;
        }

        // Tipo de pagamento
        $transactionsByType = $transactions
            ->groupBy('type')
            ->map(fn ($group) => $group->count())
            ->toArray();

        // Valor por tipo
        $amountByTypeCents = $transactions
            ->groupBy('type')
            ->map(fn ($group) => $group->sum('amount'))
            ->toArray();

        $amountByType = [];
        $amountByTypeFormatted = [];
        $amountByTypePercent = [];
        $amountByTypePercentFormatted = [];

        $amountByStatusCents = $transactions
            ->groupBy('status')
            ->map(fn ($group) => $group->sum('amount'))
            ->toArray();

        foreach ($amountByTypeCents as $type => $amountCents) {
            $amount = $toReais($amountCents);

            $amountByType[$type] = $amount;
            $amountByTypeFormatted[$type] = $formatMoney($amount);

            $percent = $totalAmountCents > 0
                ? ($amountCents / $totalAmountCents) * 100
                : 0;

            $amountByTypePercent[$type] = $percent;
            $amountByTypePercentFormatted[$type] = $formatPercent($percent);
        }

        return [
            // 'per_page'                        => $perPage,
            // 'current_page'                    => $currentPage,
            // 'transactions_on_page'            => $transactionsOnPage,

            // 'total_amount'                    => $totalAmount,
            // 'total_original_amount'           => $totalOriginalAmount,
            // 'total_fees'                      => $totalFees,
            // 'average_ticket'                  => $averageTicket,

            // 'average_take_rate'             => $averageTakeRatePercent,
            // 'average_take_rate_formatted'   => $formatPercent($averageTakeRatePercent),
            // 'amount_by_type'                => $amountByType,
            // 'amount_by_type_percent'        => $amountByTypePercent,

            /* linha 1 */
            'total_amount_formatted' => $formatMoney($totalAmount),
            'total_fees_formatted' => $formatMoney($totalFees),
            'total_original_amount_formatted' => $formatMoney($totalOriginalAmount),

            /* linha 2 */
            'total_transactions' => $totalTransactions,
            'average_ticket_formatted' => $formatMoney($averageTicket),
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
        ];
    }
}
