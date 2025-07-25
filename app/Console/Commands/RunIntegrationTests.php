<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunIntegrationTests extends Command
{
    protected $signature = 'test:integration';
    protected $description = 'Executa todos os testes de integração real em tests/Integration';

    public function handle()
    {
        $this->info("Executando testes de integração...");

        $result = shell_exec('php artisan test tests/Integration');

        $this->line($result);

        return 0;
    }
}
