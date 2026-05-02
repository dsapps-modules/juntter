<?php

namespace App\Console\Commands;

use App\Models\CheckoutEvent;
use App\Models\CheckoutSession;
use Illuminate\Console\Command;

class MarkAbandonedCheckouts extends Command
{
    protected $signature = 'checkout:mark-abandoned';

    protected $description = 'Marca sessões de checkout sem atividade como abandonadas';

    public function handle(): int
    {
        $cutoff = now()->subMinutes(30);

        CheckoutSession::query()
            ->whereNotIn('status', ['paid', 'cancelled', 'failed', 'abandoned'])
            ->whereNotNull('last_activity_at')
            ->where('last_activity_at', '<=', $cutoff)
            ->chunkById(100, function ($sessions): void {
                foreach ($sessions as $session) {
                    $session->update([
                        'status' => 'abandoned',
                        'current_step' => 'confirmation',
                    ]);

                    CheckoutEvent::query()->firstOrCreate([
                        'checkout_session_id' => $session->id,
                        'event_type' => 'checkout_abandoned',
                    ], [
                        'checkout_link_id' => $session->checkout_link_id,
                        'seller_id' => $session->seller_id,
                        'step' => $session->current_step,
                        'metadata' => ['reason' => 'inactivity'],
                    ]);
                }
            });

        $this->info('Checkout abandonados processados.');

        return self::SUCCESS;
    }
}
