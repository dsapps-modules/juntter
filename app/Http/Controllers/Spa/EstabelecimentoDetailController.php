<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Models\PaytimeEstablishment;
use App\Models\PaytimeTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstabelecimentoDetailController extends Controller
{
    public function __invoke(Request $request, PaytimeEstablishment $estabelecimento): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $user->isSuperAdminOrAdmin()) {
            abort(403, 'Acesso negado');
        }

        $recentTransactions = PaytimeTransaction::query()
            ->where('establishment_id', $estabelecimento->id)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        return response()->json([
            'establishment' => [
                'id' => $estabelecimento->id,
                'display_name' => $estabelecimento->display_name,
                'first_name' => $estabelecimento->first_name,
                'last_name' => $estabelecimento->last_name,
                'document' => $estabelecimento->document,
                'access_type' => $estabelecimento->type ?? 'ACQUIRER',
                'access_type_label' => $this->formatAccessType($estabelecimento->type),
                'email' => $estabelecimento->email ?? 'N/A',
                'phone_number' => $estabelecimento->phone_number ?? 'N/A',
                'category' => $estabelecimento->category ?? 'Geral',
                'status' => $estabelecimento->status,
                'status_label' => $this->formatStatus($estabelecimento),
                'risk' => $estabelecimento->risk,
                'risk_label' => $this->formatRisk($estabelecimento->risk),
                'active' => (bool) $estabelecimento->active,
                'revenue' => (float) $estabelecimento->revenue,
                'revenue_label' => $this->formatMoney((int) round(((float) $estabelecimento->revenue) * 100)),
                'revenue_cents' => (int) round(((float) $estabelecimento->revenue) * 100),
                'format' => $estabelecimento->type === 'COMPANY' ? 'LTDA' : 'MEI',
                'gmv' => null,
                'birthdate' => null,
                'city' => $estabelecimento->address_json['city'] ?? null,
                'responsible' => trim((string) ($estabelecimento->responsible_json['name'] ?? '')) ?: $estabelecimento->display_name,
                'address' => [
                    'street' => $estabelecimento->address_json['street'] ?? 'N/A',
                    'number' => $estabelecimento->address_json['number'] ?? 'N/A',
                    'neighborhood' => $estabelecimento->address_json['neighborhood'] ?? 'N/A',
                    'city' => $estabelecimento->address_json['city'] ?? 'N/A',
                    'state' => $estabelecimento->address_json['state'] ?? 'N/A',
                    'zip_code' => $estabelecimento->address_json['zip_code'] ?? $estabelecimento->address_json['postal_code'] ?? 'N/A',
                    'complement' => $estabelecimento->address_json['complement'] ?? 'N/A',
                    'formatted' => $estabelecimento->formatted_address,
                ],
                'financial' => [
                    'revenue' => $this->formatMoney((int) round(((float) $estabelecimento->revenue) * 100)),
                    'gmv' => 'N/A',
                    'birthdate' => 'N/A',
                    'format' => $estabelecimento->type === 'COMPANY' ? 'LTDA' : 'MEI',
                ],
                'timeline' => $recentTransactions->map(function (PaytimeTransaction $transaction): array {
                    return [
                        'color' => $this->timelineColor($transaction->status),
                        'title' => $this->formatTransactionType($transaction->type),
                        'description' => $this->formatTimelineDescription($transaction),
                    ];
                })->values(),
            ],
            'recent_transactions' => $recentTransactions->map(function (PaytimeTransaction $transaction): array {
                return [
                    'id' => $transaction->id,
                    'type' => $this->formatTransactionType($transaction->type),
                    'status' => $this->formatTransactionStatus($transaction->status),
                    'amount' => $this->formatMoney((int) $transaction->amount),
                    'created_at' => Carbon::parse($transaction->created_at)->format('d/m/Y H:i'),
                ];
            })->values(),
        ]);
    }

    private function formatStatus(PaytimeEstablishment $establishment): string
    {
        if (! $establishment->active) {
            return 'Inativo';
        }

        return match ($establishment->status) {
            'BLOCKED', 'SUSPENDED' => 'Bloqueado',
            'REVIEW' => 'Em análise',
            default => 'Ativo',
        };
    }

    private function formatRisk(?string $risk): string
    {
        return match ($risk) {
            'LOW' => 'Baixo',
            'MEDIUM' => 'Médio',
            'HIGH' => 'Alto',
            default => $risk !== null && $risk !== '' ? $risk : 'N/A',
        };
    }

    private function formatAccessType(?string $type): string
    {
        return match ($type) {
            'ACQUIRER' => 'Adquirente',
            'BANKING' => 'Bancário',
            default => $type !== null && $type !== '' ? $type : 'N/A',
        };
    }

    private function formatTransactionType(?string $type): string
    {
        return match ($type) {
            'CREDIT' => 'Crédito',
            'DEBIT' => 'Débito',
            'PIX' => 'PIX',
            'BILLET' => 'Boleto',
            default => 'Transação',
        };
    }

    private function formatTransactionStatus(?string $status): string
    {
        return match ($status) {
            'PAID' => 'Pago',
            'APPROVED' => 'Aprovado',
            'PENDING' => 'Pendente',
            'PROCESSING' => 'Processando',
            'FAILED' => 'Falhado',
            'REFUNDED' => 'Devolvido',
            default => $status !== null && $status !== '' ? $status : 'N/A',
        };
    }

    private function formatTimelineDescription(PaytimeTransaction $transaction): string
    {
        $amount = $this->formatMoney((int) $transaction->amount);
        $status = $this->formatTransactionStatus($transaction->status);
        $date = Carbon::parse($transaction->created_at)->format('d/m/Y H:i');

        return "{$status} • {$amount} • {$date}";
    }

    private function timelineColor(?string $status): string
    {
        return match ($status) {
            'PAID', 'APPROVED' => 'green',
            'FAILED', 'REFUNDED' => 'red',
            'PENDING', 'PROCESSING' => 'gold',
            default => 'blue',
        };
    }

    private function formatMoney(int $amountInCents): string
    {
        return 'R$ '.number_format($amountInCents / 100, 2, ',', '.');
    }
}
