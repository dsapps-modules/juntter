<?php

namespace App\Console\Commands;

use App\Models\PaytimeEstablishment;
use App\Models\PaytimeTransaction;
use App\Services\BoletoService;
use App\Services\PaytimeTransactionSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPaytimeBillets extends Command
{
    protected $signature = 'paytime:sync-billets {--months= : Months to sync (comma separated, e.g. 11,12)} {--year= : Year to sync (e.g. 2024)}';

    protected $description = 'Sync billets from Paytime to local database';

    protected int $perPage = 1000;

    protected $boletoService;

    protected PaytimeTransactionSyncService $paytimeTransactionSyncService;

    public function __construct(
        BoletoService $boletoService,
        PaytimeTransactionSyncService $paytimeTransactionSyncService
    ) {
        parent::__construct();
        $this->boletoService = $boletoService;
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

            $this->info('Starting manual sync for billets... Months: '.implode(', ', $months)." of $year");
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
            // Incremental sync for billets
            $lastRecord = PaytimeTransaction::where('type', 'BILLET')->max('created_at');

            if ($lastRecord) {
                $start = Carbon::parse($lastRecord);
                $end = now();

                $this->info('Starting incremental sync for billets from '.$start->toDateTimeString().' to '.$end->toDateTimeString());

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
                $this->info('No billet records found. Falling back to default sync (current and previous month).');
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

        $establishments = PaytimeEstablishment::select('id')->get();
        $this->info('Found '.$establishments->count().' establishments to sync billets.');

        foreach ($periods as $period) {
            $startDate = $period['start'];
            $endDate = $period['end'];

            $this->info("Syncing billets for period: $startDate to $endDate...");
            $bar = $this->output->createProgressBar($establishments->count());
            $bar->start();

            foreach ($establishments as $establishment) {
                $this->syncBillets($startDate, $endDate, $establishment->id);
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        $this->info('Billets sync completed successfully!');
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
                'perPage' => $this->perPage,
                'page' => $page,
                'filters' => json_encode($queryFilter),
            ];

            if ($establishmentId) {
                $filters['extra_headers'] = ['establishment_id' => $establishmentId];
            }

            try {
                $response = $this->boletoService->listarBoletos($filters);
                $items = $response['data'] ?? [];

                foreach ($items as $item) {
                    $this->paytimeTransactionSyncService->sync($item, [
                        'default_type' => 'BILLET',
                        'default_establishment_id' => $establishmentId,
                        'created_at' => $item['created_at'] ?? null,
                        'metadata' => $item,
                    ]);
                }

                $page++;
            } catch (\Exception $e) {
                Log::error("Error syncing billets for establishment $establishmentId: ".$e->getMessage());
                break;
            }
        } while (! empty($items));
    }
}
