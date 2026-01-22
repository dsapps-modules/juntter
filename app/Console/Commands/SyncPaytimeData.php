<?php

namespace App\Console\Commands;

use App\Models\PaytimeTransaction;
use App\Models\PaytimeEstablishment;
use App\Services\BoletoService;
use App\Services\TransacaoService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPaytimeData extends Command
{
    protected $signature = 'paytime:sync {--months= : Months to sync (comma separated, e.g. 11,12)} {--year= : Year to sync (e.g. 2024)}';
    protected $description = 'Sync transactions and billets from Paytime to local database';

    protected $transacaoService;
    protected $boletoService;

    public function __construct(
        TransacaoService $transacaoService,
        BoletoService $boletoService
    ) {
        parent::__construct();
        $this->transacaoService = $transacaoService;
        $this->boletoService = $boletoService;
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

            $this->info("Starting manual sync for months: " . implode(', ', $months) . " of $year");
            $periods = [];
            foreach ($months as $m) {
                $periods[] = [
                    'month' => (int) $m,
                    'year' => (int) $year,
                    'start' => Carbon::createFromDate((int) $year, (int) $m, 1)->startOfMonth()->format('Y-m-d'),
                    'end' => Carbon::createFromDate((int) $year, (int) $m, 1)->endOfMonth()->format('Y-m-d')
                ];
            }
        } else {
            // Incremental sync
            $lastRecord = PaytimeTransaction::max('created_at');

            if ($lastRecord) {
                $start = Carbon::parse($lastRecord);
                $end = now();

                $this->info("Starting incremental sync from " . $start->toDateTimeString() . " to " . $end->toDateTimeString());

                $periods = [];
                $tempDate = $start->copy()->startOfMonth();
                while ($tempDate <= $end) {
                    $periods[] = [
                        'month' => $tempDate->month,
                        'year' => $tempDate->year,
                        'start' => ($tempDate->format('Y-m') === $start->format('Y-m')) ? $start->format('Y-m-d') : $tempDate->startOfMonth()->format('Y-m-d'),
                        'end' => ($tempDate->format('Y-m') === $end->format('Y-m')) ? $end->format('Y-m-d') : $tempDate->copy()->endOfMonth()->format('Y-m-d')
                    ];
                    $tempDate->addMonth();
                }
            } else {
                $this->info("No records found. Falling back to default sync (current and previous month).");
                $months = explode(',', $defaultMonths);
                $periods = [];
                foreach ($months as $m) {
                    $periods[] = [
                        'month' => (int) $m,
                        'year' => $currentYear,
                        'start' => Carbon::createFromDate($currentYear, (int) $m, 1)->startOfMonth()->format('Y-m-d'),
                        'end' => Carbon::createFromDate($currentYear, (int) $m, 1)->endOfMonth()->format('Y-m-d')
                    ];
                }
            }
        }

        $establishments = PaytimeEstablishment::select('id')->get();
        $this->info("Found " . $establishments->count() . " establishments to sync billets.");

        foreach ($periods as $period) {
            $month = $period['month'];
            $y = $period['year'];
            $startDate = $period['start'];
            $endDate = $period['end'];

            $this->info("Syncing $month/$y (from $startDate to $endDate)...");

            // Sync Transactions (Global fetch usually works for these)
            $this->syncTransactions($startDate, $endDate);

            // Sync Billets (Required per-establishment for completeness)
            $this->info("Syncing billets for " . $establishments->count() . " establishments ($month/$y)...");
            $bar = $this->output->createProgressBar($establishments->count());
            $bar->start();

            foreach ($establishments as $establishment) {
                $this->syncBillets($startDate, $endDate, $establishment->id);
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info('Sync completed successfully!');
    }

    private function syncTransactions($startDate, $endDate)
    {
        $page = 1;
        do {
            $filters = [
                'perPage' => 100,
                'page' => $page,
                'filters' => json_encode([
                    'created_at' => ['min' => $startDate, 'max' => $endDate]
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
                    PaytimeTransaction::updateOrCreate(
                        ['external_id' => $item['_id'] ?? $item['id']],
                        [
                            // Validar se 'establishment' vem populado ou se precisa de fallback
                            'establishment_id' => $item['establishment']['id'] ?? ($item['establishment_id'] ?? 'UNKNOWN'),
                            'type' => $item['type'] ?? 'UNKNOWN',
                            'status' => $item['status'] ?? 'UNKNOWN',
                            'amount' => $item['amount'] ?? 0,
                            'original_amount' => $item['original_amount'] ?? ($item['amount'] ?? 0),
                            'fees' => $item['fees'] ?? 0,
                            'installments' => $item['installments'] ?? 1,
                            'created_at' => isset($item['created_at']) ? Carbon::parse($item['created_at']) : now(),
                            'metadata' => json_encode($item)
                        ]
                    );
                }

                $this->info("Synced " . count($items) . " transactions (Page $page) for period $startDate");
                $page++;
            } catch (\Exception $e) {
                Log::error("Error syncing transactions: " . $e->getMessage());
                break;
            }
        } while (!empty($items));
    }

    private function syncBillets($startDate = null, $endDate = null, $establishmentId = null)
    {
        $page = 1;
        do {
            $queryFilter = [];
            if ($startDate && $endDate) {
                $queryFilter['created_at'] = ['min' => $startDate, 'max' => $endDate];
            }

            $filters = [
                'perPage' => 100,
                'page' => $page,
                'filters' => json_encode($queryFilter)
            ];

            if ($establishmentId) {
                // The API requires establishment_id in header to see all records
                $filters['extra_headers'] = ['establishment_id' => $establishmentId];
            }

            try {
                $response = $this->boletoService->listarBoletos($filters);
                $items = $response['data'] ?? [];
                if (count($items) > 0) {
                    // Check establishments
                }

                foreach ($items as $item) {
                    PaytimeTransaction::updateOrCreate(
                        ['external_id' => $item['_id'] ?? $item['id']],
                        [
                            'establishment_id' => $item['establishment']['id'] ?? ($item['establishment_id'] ?? $establishmentId ?? 'UNKNOWN'),
                            'type' => $item['type'] ?? 'BILLET',
                            'status' => $item['status'] ?? 'UNKNOWN',
                            'amount' => $item['amount'] ?? 0,
                            'original_amount' => $item['original_amount'] ?? ($item['amount'] ?? 0),
                            'fees' => $item['fees'] ?? 0,
                            'gateway_key' => $item['gateway_key'] ?? null,
                            'expiration_at' => isset($item['expiration_at']) ? Carbon::parse($item['expiration_at']) : null,
                            'created_at' => isset($item['created_at']) ? Carbon::parse($item['created_at']) : now(),
                            'metadata' => json_encode($item)
                        ]
                    );
                }

                // $this->info("Synced " . count($items) . " billets (Page $page) for period $startDate");
                $page++;
            } catch (\Exception $e) {
                Log::error("Error syncing billets for establishment $establishmentId: " . $e->getMessage());
                break;
            }
        } while (!empty($items));
    }
}
