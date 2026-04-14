<?php

namespace App\Console\Commands;

use App\Models\PaytimeTransaction;
use App\Services\PaytimeTransactionSyncService;
use App\Services\TransacaoService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPaytimeTransactions extends Command
{
    protected $signature = 'paytime:sync-transactions {--months= : Months to sync (comma separated, e.g. 11,12)} {--year= : Year to sync (e.g. 2024)}';

    protected $description = 'Sync transactions from Paytime to local database';

    protected int $perPage = 1000;

    protected $transacaoService;

    protected PaytimeTransactionSyncService $paytimeTransactionSyncService;

    public function __construct(
        TransacaoService $transacaoService,
        PaytimeTransactionSyncService $paytimeTransactionSyncService
    ) {
        parent::__construct();
        $this->transacaoService = $transacaoService;
        $this->paytimeTransactionSyncService = $paytimeTransactionSyncService;
    }

    public function handle()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $previousMonth = now()->subMonth()->month;

        $defaultMonths = (string) $currentMonth;
        if ($currentMonth != $previousMonth) {
            $defaultMonths = "$previousMonth,$currentMonth";
        }

        if ($this->option('months') || $this->option('year')) {
            $months = explode(',', $this->option('months') ?? $currentMonth);
            $year = $this->option('year') ?? $currentYear;
            sort($months);

            $this->info('Starting manual sync for transactions... Months: '.implode(', ', $months)." of $year");
            $periods = [];
            foreach ($months as $m) {
                $periods[] = [
                    'month' => (int) $m,
                    'year' => (int) $year,
                    'start' => Carbon::createFromDate((int) $year, (int) $m, 1)->startOfMonth()->format('Y-m-d'),
                    'end' => Carbon::createFromDate((int) $year, (int) $m, 1)->endOfMonth()->format('Y-m-d'),
                ];
            }
        } else {
            // Incremental sync
            $lastRecord = PaytimeTransaction::where('type', '!=', 'BILLET')->max('created_at');

            if ($lastRecord) {
                $start = Carbon::parse($lastRecord);
                $end = now();

                $this->info('Starting incremental sync for transactions from '.$start->toDateTimeString().' to '.$end->toDateTimeString());

                $periods = [];
                $tempDate = $start->copy()->startOfMonth();
                while ($tempDate <= $end) {
                    $periods[] = [
                        'month' => $tempDate->month,
                        'year' => $tempDate->year,
                        'start' => ($tempDate->format('Y-m') === $start->format('Y-m')) ? $start->format('Y-m-d') : $tempDate->startOfMonth()->format('Y-m-d'),
                        'end' => ($tempDate->format('Y-m') === $end->format('Y-m')) ? $end->format('Y-m-d') : $tempDate->copy()->endOfMonth()->format('Y-m-d'),
                    ];
                    $tempDate->addMonth();
                }
            } else {
                $this->info('No transaction records found. Falling back to default sync (current and previous month).');
                $months = explode(',', $defaultMonths);
                $periods = [];
                foreach ($months as $m) {
                    $periods[] = [
                        'month' => (int) $m,
                        'year' => $currentYear,
                        'start' => Carbon::createFromDate($currentYear, (int) $m, 1)->startOfMonth()->format('Y-m-d'),
                        'end' => Carbon::createFromDate($currentYear, (int) $m, 1)->endOfMonth()->format('Y-m-d'),
                    ];
                }
            }
        }

        foreach ($periods as $period) {
            $startDate = $period['start'];
            $endDate = $period['end'];
            $this->info("Syncing transactions for period: $startDate to $endDate");
            $this->syncTransactions($startDate, $endDate);
        }

        $this->info('Transactions sync completed successfully!');
    }

    private function syncTransactions($startDate, $endDate)
    {
        $page = 1;
        do {
            $filters = [
                'perPage' => $this->perPage,
                'page' => $page,
                'filters' => json_encode([
                    'created_at' => ['min' => $startDate, 'max' => $endDate],
                ]),
                'sorters' => json_encode([
                    'column' => 'created_at',
                    'direction' => 'ASC',
                ]),
            ];

            try {
                $response = $this->transacaoService->listarTransacoes($filters);
                $items = $response['data'] ?? [];

                foreach ($items as $item) {
                    $this->paytimeTransactionSyncService->sync($item, [
                        'default_type' => 'UNKNOWN',
                        'created_at' => $item['created_at'] ?? null,
                        'metadata' => $item,
                    ]);
                }

                $this->info('Synced '.count($items)." transactions (Page $page)");
                $page++;
            } catch (\Exception $e) {
                Log::error('Error syncing transactions: '.$e->getMessage());
                $this->error('Error syncing transactions: '.$e->getMessage());
                break;
            }
        } while (! empty($items));
    }
}
