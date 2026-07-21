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

    protected int $maxConsecutivePageErrors = 3;

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

    public function handle(): int
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

        $totalErrors = 0;

        foreach ($periods as $period) {
            $startDate = $period['start'];
            $endDate = $period['end'];
            $this->info("Syncing transactions for period: $startDate to $endDate");
            $totalErrors += $this->syncTransactions($startDate, $endDate);
        }

        if ($totalErrors > 0) {
            $this->warn("Transactions sync completed with {$totalErrors} error(s).");
        } else {
            $this->info('Transactions sync completed successfully!');
        }

        return self::SUCCESS;
    }

    private function syncTransactions(string $startDate, string $endDate): int
    {
        $page = 1;
        $errors = 0;
        $consecutivePageErrors = 0;

        while (true) {
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

            $items = [];

            try {
                $response = $this->transacaoService->listarTransacoes($filters);
                $items = is_array($response['data'] ?? null) ? $response['data'] : [];
                $consecutivePageErrors = 0;
            } catch (\Throwable $e) {
                $errors++;
                $consecutivePageErrors++;

                Log::error('Error syncing transactions page', [
                    'page' => $page,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'message' => $e->getMessage(),
                    'exception' => $e::class,
                ]);

                $this->error("Error syncing transactions (Page $page): ".$e->getMessage());

                if ($consecutivePageErrors >= $this->maxConsecutivePageErrors) {
                    $this->error("Stopping transaction sync for period $startDate to $endDate after {$this->maxConsecutivePageErrors} consecutive page errors.");
                    break;
                }

                $page++;

                continue;
            }

            if (empty($items)) {
                break;
            }

            $syncedItems = 0;

            foreach ($items as $item) {
                try {
                    $this->paytimeTransactionSyncService->sync($item, [
                        'default_type' => 'UNKNOWN',
                        'created_at' => $item['created_at'] ?? null,
                        'metadata' => $item,
                    ]);
                    $syncedItems++;
                } catch (\Throwable $e) {
                    $errors++;

                    Log::warning('Error syncing transaction item', [
                        'page' => $page,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'item_id' => $item['_id'] ?? $item['id'] ?? null,
                        'message' => $e->getMessage(),
                        'exception' => $e::class,
                    ]);

                    $this->error('Error syncing transaction item on page '.$page.': '.$e->getMessage());
                }
            }

            $this->info('Synced '.$syncedItems." transactions (Page $page) - ".date('d/m/y h:i:s'));
            $page++;
        }

        return $errors;
    }
}
