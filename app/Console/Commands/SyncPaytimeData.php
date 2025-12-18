<?php

namespace App\Console\Commands;

use App\Models\PaytimeTransaction;
use App\Services\BoletoService;
use App\Services\TransacaoService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPaytimeData extends Command
{
    protected $signature = 'paytime:sync {--months= : Months to sync (comma separated, e.g. 11,12)} {--year=2025}';
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
        $previousYear = now()->subMonth()->year;

        $defaultMonths = $currentMonth;
        if ($currentMonth != $previousMonth) {
            $defaultMonths = "$previousMonth,$currentMonth";
        }

        $months = explode(',', $this->option('months') ?? $defaultMonths);
        $year = $this->option('year') ?? $currentYear;

        // Ensure months are sorted to handle range correctly
        sort($months);

        $this->info("Starting global sync for months: " . implode(', ', $months) . " of $year");

        // Calculate global start and end for billets if multiple months are provided
        $firstMonth = reset($months);
        $lastMonth = end($months);
        $globalStart = Carbon::createFromDate($year, $firstMonth, 1)->startOfMonth()->format('Y-m-d');
        $globalEnd = Carbon::createFromDate($year, $lastMonth, 1)->endOfMonth()->format('Y-m-d');

        $this->info("Syncing billets from $globalStart to $globalEnd...");
        $this->syncBillets($globalStart, $globalEnd);

        foreach ($months as $month) {
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');

            $this->info("Syncing $month/$year...");
            $this->syncTransactions($startDate, $endDate);
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

    private function syncBillets($startDate = null, $endDate = null)
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

            try {
                $response = $this->boletoService->listarBoletos($filters);
                $items = $response['data'] ?? [];

                foreach ($items as $item) {
                    PaytimeTransaction::updateOrCreate(
                        ['external_id' => $item['_id'] ?? $item['id']],
                        [
                            'establishment_id' => $item['establishment']['id'] ?? ($item['establishment_id'] ?? 'UNKNOWN'),
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

                $this->info("Synced " . count($items) . " billets (Page $page) for period $startDate");
                $page++;
            } catch (\Exception $e) {
                Log::error("Error syncing billets: " . $e->getMessage());
                break;
            }
        } while (!empty($items));
    }
}
