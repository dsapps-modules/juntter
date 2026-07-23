<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ConsoleScheduleTest extends TestCase
{
    public function test_paytime_establishments_are_scheduled_for_1am(): void
    {
        Artisan::call('schedule:list');

        $output = Artisan::output();

        $this->assertStringContainsString('paytime:sync-establishments', $output);
        $this->assertMatchesRegularExpression('/^\s*0\s+1\s+\*\s+\*\s+\*\s+php artisan paytime:sync-establishments/m', $output);
    }
}
