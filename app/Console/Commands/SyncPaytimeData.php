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
        $months = explode(',', $this->option('months') ?? '11,12');
        $year = $this->option('year') ?? '2025';

        $this->info("Starting global sync for months: " . implode(', ', $months) . " of $year");

        foreach ($months as $month) {
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');

            $this->info("Syncing $month/$year...");
            $this->syncTransactions($startDate, $endDate);
            $this->syncBillets($startDate, $endDate);
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

    private function syncBillets($startDate, $endDate)
    {
        $page = 1;
        do {
            $filters = [
                'perPage' => 100,
                'page' => $page,
                'filters' => json_encode([
                    'created_at' => ['min' => $startDate, 'max' => $endDate]
                ])
            ];

            try {
                $response = $this->boletoService->listarBoletos($filters);
                $items = $response['data'] ?? [];

                foreach ($items as $item) {
                    PaytimeTransaction::updateOrCreate(
                        ['external_id' => $item['_id'] ?? $item['id']],
                        [
                            'establishment_id' => $item['establishment']['id'] ?? ($item['establishment_id'] ?? 'UNKNOWN'),
                            'type' => 'BOLETO',
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
