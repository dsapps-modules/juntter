<?php

namespace App\Console\Commands;

use App\Mail\CheckoutRecoveryMail;
use App\Models\AbandonedCheckoutRecovery;
use App\Models\CheckoutSession;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendCheckoutRecoveryMessages extends Command
{
    private const RECOVERY_STEPS = [
        1 => 240,
        2 => 1440,
        3 => 4320,
    ];

    protected $signature = 'checkout:send-recovery-messages';

    protected $description = 'Cria e envia a sequência de recuperação para checkouts abandonados';

    public function handle(): int
    {
        $this->seedRecoverySequence();
        $this->sendDueRecoveries();

        $this->info('Recuperações de checkout processadas.');

        return self::SUCCESS;
    }

    private function seedRecoverySequence(): void
    {
        CheckoutSession::query()
            ->with(['checkoutLink.product', 'checkoutLink.seller'])
            ->where('status', 'abandoned')
            ->whereNotNull('customer_email')
            ->chunkById(100, function ($sessions): void {
                foreach ($sessions as $session) {
                    if ($this->hasPaidOrder($session)) {
                        $this->markRecoveriesAsSkipped($session);

                        continue;
                    }

                    if ($session->abandonedRecoveries()->where('channel', 'email')->exists()) {
                        continue;
                    }

                    $abandonedAt = $session->last_activity_at?->copy() ?? now();

                    foreach (self::RECOVERY_STEPS as $sequenceStep => $delayMinutes) {
                        AbandonedCheckoutRecovery::query()->create([
                            'checkout_session_id' => $session->id,
                            'seller_id' => $session->seller_id,
                            'channel' => 'email',
                            'sequence_step' => $sequenceStep,
                            'status' => 'pending',
                            'scheduled_at' => $abandonedAt->copy()->addMinutes($delayMinutes),
                        ]);
                    }
                }
            });
    }

    private function sendDueRecoveries(): void
    {
        AbandonedCheckoutRecovery::query()
            ->with(['checkoutSession.checkoutLink.product', 'checkoutSession.checkoutLink.seller', 'checkoutSession.orders'])
            ->where('channel', 'email')
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->chunkById(100, function ($recoveries): void {
                foreach ($recoveries as $recovery) {
                    $checkoutSession = $recovery->checkoutSession;

                    if (! $checkoutSession) {
                        $recovery->update([
                            'status' => 'failed',
                            'error_message' => 'checkout_session_missing',
                        ]);

                        continue;
                    }

                    $checkoutLink = $checkoutSession->checkoutLink;

                    if (! $checkoutLink || ! $checkoutLink->isActive() || ! $checkoutLink->product?->isActive()) {
                        $recovery->update([
                            'status' => 'skipped',
                            'sent_at' => now(),
                            'error_message' => 'checkout_unavailable',
                        ]);

                        continue;
                    }

                    if ($this->hasPaidOrder($checkoutSession)) {
                        $recovery->update([
                            'status' => 'skipped',
                            'sent_at' => now(),
                            'error_message' => 'order_paid',
                        ]);

                        continue;
                    }

                    if (blank($checkoutSession->customer_email)) {
                        $recovery->update([
                            'status' => 'skipped',
                            'sent_at' => now(),
                            'error_message' => 'customer_email_missing',
                        ]);

                        continue;
                    }

                    $recoveryUrl = route('checkout.public.recover', $checkoutSession->session_token);

                    try {
                        Mail::to((string) $checkoutSession->customer_email)->send(new CheckoutRecoveryMail(
                            checkoutSession: $checkoutSession,
                            sequenceStep: (int) $recovery->sequence_step,
                            recoveryUrl: $recoveryUrl,
                        ));

                        $recovery->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                            'error_message' => null,
                        ]);
                    } catch (Throwable $throwable) {
                        $recovery->update([
                            'status' => 'failed',
                            'sent_at' => now(),
                            'error_message' => mb_substr($throwable->getMessage(), 0, 1000),
                        ]);
                    }
                }
            });
    }

    private function markRecoveriesAsSkipped(CheckoutSession $session): void
    {
        $updated = AbandonedCheckoutRecovery::query()
            ->where('checkout_session_id', $session->id)
            ->where('channel', 'email')
            ->where('status', 'pending')
            ->update([
                'status' => 'skipped',
                'sent_at' => now(),
                'error_message' => 'order_paid',
            ]);

        if ($updated > 0) {
            return;
        }

        AbandonedCheckoutRecovery::query()->firstOrCreate([
            'checkout_session_id' => $session->id,
            'channel' => 'email',
            'sequence_step' => 1,
        ], [
            'seller_id' => $session->seller_id,
            'status' => 'skipped',
            'scheduled_at' => now(),
            'sent_at' => now(),
            'error_message' => 'order_paid',
        ]);
    }

    private function hasPaidOrder(CheckoutSession $session): bool
    {
        return Order::query()
            ->where('checkout_session_id', $session->id)
            ->whereIn('status', ['paid', 'authorized'])
            ->exists();
    }
}
