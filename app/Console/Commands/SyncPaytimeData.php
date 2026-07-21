<?php

namespace App\Console\Commands;

use App\Services\BoletoService;
use App\Services\TransacaoService;
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

    public function handle(): int
    {
        $this->info('Starting full Paytime sync (Transactions and Billets) in '.date('d/m/y h:i:s'));

        $options = [];
        if ($this->option('months')) {
            $options['--months'] = $this->option('months');
        }
        if ($this->option('year')) {
            $options['--year'] = $this->option('year');
        }

        $totalErrors = 0;

        $this->info('Step 1/2: Syncing Transactions...');
        try {
            $totalErrors += (int) $this->call('paytime:sync-transactions', $options);
        } catch (\Throwable $e) {
            $totalErrors++;
            Log::error('Error running paytime:sync-transactions from paytime:sync', [
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);
            $this->error('Error running paytime:sync-transactions: '.$e->getMessage());
        }

        $this->info('Step 2/2: Syncing Billets...');
        try {
            $totalErrors += (int) $this->call('paytime:sync-billets', $options);
        } catch (\Throwable $e) {
            $totalErrors++;
            Log::error('Error running paytime:sync-billets from paytime:sync', [
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);
            $this->error('Error running paytime:sync-billets: '.$e->getMessage());
        }

        if ($totalErrors > 0) {
            $this->warn("Full sync completed with {$totalErrors} error(s) in ".date('d/m/y h:i:s'));
        } else {
            $this->info('Full sync completed successfully in '.date('d/m/y h:i:s'));
        }

        return self::SUCCESS;
    }
}
