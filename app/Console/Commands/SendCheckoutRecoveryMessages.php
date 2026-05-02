<?php

namespace App\Console\Commands;

use App\Models\AbandonedCheckoutRecovery;
use App\Models\CheckoutSession;
use App\Models\Order;
use Illuminate\Console\Command;

class SendCheckoutRecoveryMessages extends Command
{
    protected $signature = 'checkout:send-recovery-messages';

    protected $description = 'Cria registros de recuperação para checkouts abandonados sem pagamento';

    public function handle(): int
    {
        CheckoutSession::query()
            ->where('status', 'abandoned')
            ->whereNotNull('customer_email')
            ->chunkById(100, function ($sessions): void {
                foreach ($sessions as $session) {
                    $hasPaidOrder = Order::query()
                        ->where('checkout_session_id', $session->id)
                        ->where('status', 'paid')
                        ->exists();

                    if ($hasPaidOrder) {
                        AbandonedCheckoutRecovery::query()->firstOrCreate([
                            'checkout_session_id' => $session->id,
                            'channel' => 'email',
                        ], [
                            'seller_id' => $session->seller_id,
                            'status' => 'skipped',
                            'scheduled_at' => now(),
                            'error_message' => 'order_paid',
                        ]);

                        continue;
                    }

                    AbandonedCheckoutRecovery::query()->firstOrCreate([
                        'checkout_session_id' => $session->id,
                        'channel' => 'email',
                    ], [
                        'seller_id' => $session->seller_id,
                        'status' => 'sent',
                        'scheduled_at' => now(),
                        'sent_at' => now(),
                    ]);
                }
            });

        $this->info('Recuperações de checkout processadas.');

        return self::SUCCESS;
    }
}
