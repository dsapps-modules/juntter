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
        $this->info('Starting full Paytime sync (Transactions and Billets)...');

        $options = [];
        if ($this->option('months')) {
            $options['--months'] = $this->option('months');
        }
        if ($this->option('year')) {
            $options['--year'] = $this->option('year');
        }

        $this->info('Step 1/2: Syncing Transactions...');
        $this->call('paytime:sync-transactions', $options);

        $this->info('Step 2/2: Syncing Billets...');
        $this->call('paytime:sync-billets', $options);

        $this->info('Full sync completed successfully!');
    }
}

