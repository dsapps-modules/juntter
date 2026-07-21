<?php

namespace App\Http\Controllers;

use App\Models\CheckoutSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SellerAbandonedCheckoutController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $abandonedSessions = CheckoutSession::query()
            ->where('seller_id', $request->user()->id)
            ->where('status', 'abandoned')
            ->whereDoesntHave('orders', function ($query): void {
                $query->whereIn('status', ['paid', 'authorized']);
            })
            ->with([
                'checkoutLink.product',
                'abandonedRecoveries' => function ($query): void {
                    $query->orderBy('sequence_step');
                },
            ])
            ->latest('last_activity_at')
            ->get()
            ->map(function (CheckoutSession $checkoutSession): array {
                $recoveries = $checkoutSession->abandonedRecoveries;
                $nextRecovery = $recoveries->firstWhere('status', 'pending');
                $lastRecovery = $recoveries->last();

                return [
                    'id' => $checkoutSession->id,
                    'session_token' => $checkoutSession->session_token,
                    'customer_name' => $checkoutSession->customer_name,
                    'customer_email' => $checkoutSession->customer_email,
                    'customer_phone' => $checkoutSession->customer_phone,
                    'product_name' => $checkoutSession->checkoutLink?->product?->name,
                    'link_name' => $checkoutSession->checkoutLink?->name,
                    'total' => (float) $checkoutSession->total,
                    'abandoned_at' => optional($checkoutSession->last_activity_at)->toIso8601String(),
                    'recoveries_count' => $recoveries->count(),
                    'sent_recoveries_count' => $recoveries->where('status', 'sent')->count(),
                    'pending_recoveries_count' => $recoveries->where('status', 'pending')->count(),
                    'recovery_status' => $this->resolveRecoveryStatus($recoveries),
                    'next_recovery_step' => $nextRecovery?->sequence_step,
                    'next_recovery_at' => optional($nextRecovery?->scheduled_at)->toIso8601String(),
                    'last_recovery_status' => $lastRecovery?->status,
                    'last_recovery_at' => optional($lastRecovery?->sent_at ?? $lastRecovery?->scheduled_at)->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'abandoned_sessions' => $abandonedSessions,
        ]);
    }

    private function resolveRecoveryStatus(Collection $recoveries): string
    {
        if ($recoveries->isEmpty()) {
            return 'pending';
        }

        if ($recoveries->contains(fn ($recovery): bool => $recovery->status === 'failed')) {
            return 'failed';
        }

        if ($recoveries->contains(fn ($recovery): bool => $recovery->status === 'sent')) {
            return 'in_progress';
        }

        if ($recoveries->contains(fn ($recovery): bool => $recovery->status === 'skipped')) {
            return 'skipped';
        }

        return 'pending';
    }
}
