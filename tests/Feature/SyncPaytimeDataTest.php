<?php

namespace Tests\Feature;

use App\Console\Commands\SyncPaytimeData;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class SyncPaytimeDataTest extends TestCase
{
    public function test_it_continues_running_the_second_step_when_the_first_step_fails(): void
    {
        $command = new class($this->createMock(\App\Services\TransacaoService::class), $this->createMock(\App\Services\BoletoService::class)) extends SyncPaytimeData
        {
            public int $transactionsCalls = 0;

            public int $billetsCalls = 0;

            public function call($command, array $arguments = [])
            {
                if ($command === 'paytime:sync-transactions') {
                    $this->transactionsCalls++;

                    throw new \RuntimeException('transactions failed');
                }

                if ($command === 'paytime:sync-billets') {
                    $this->billetsCalls++;

                    return 0;
                }

                return parent::call($command, $arguments);
            }
        };

        $command->setLaravel($this->app);

        $exitCode = $command->run(
            new ArrayInput([
                '--months' => '4',
                '--year' => '2026',
            ]),
            new BufferedOutput
        );

        $this->assertSame(0, $exitCode);
        $this->assertSame(1, $command->transactionsCalls);
        $this->assertSame(1, $command->billetsCalls);
    }
}
