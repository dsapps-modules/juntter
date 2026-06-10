<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Services\BalanceService;
use App\Services\TransacaoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Throwable;

class CobrancaSaldoExtratoController extends Controller
{
    public function __construct(
        private readonly BalanceService $balanceService,
        private readonly TransacaoService $transacaoService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('vendedor');

        $sellerName = trim((string) $user->name) !== '' ? (string) $user->name : 'Vendedor';
        $establishmentId = $user->getEstabelecimentoId();
        $selectedPeriod = $this->resolveSelectedPeriod($request->string('period')->toString());

        if ($establishmentId === null || (string) $establishmentId === '') {
            return response()->json([
                'seller_name' => $sellerName,
                'establishment' => null,
                'balance' => $this->emptyBalance(),
                'summary' => $this->emptySummary(),
                'rows' => [],
                'message' => 'Nenhum estabelecimento foi vinculado ao usuário autenticado.',
                'actions' => $this->buildActions(),
                'selected_period' => $selectedPeriod,
            ]);
        }

        $filters = $this->buildFilters((string) $establishmentId, $selectedPeriod);

        $balanceResponse = [];
        $extractResponse = [];
        $warnings = [];

        try {
            $balanceResponse = $this->balanceService->saldoAtual($filters);
        } catch (Throwable $throwable) {
            $warnings[] = 'Não foi possível carregar o saldo: '.$throwable->getMessage();
        }

        try {
            $extractResponse = $this->transacaoService->consultarExtratoEstabelecimento($filters);
        } catch (Throwable $throwable) {
            $warnings[] = 'Não foi possível carregar o extrato: '.$throwable->getMessage();
        }

        $balance = $this->normalizeBalance($balanceResponse);
        $rows = $this->normalizeExtractRows($extractResponse);
        $summary = $this->buildSummary($balance, $rows);

        return response()->json([
            'seller_name' => $sellerName,
            'establishment' => [
                'id' => (string) $establishmentId,
                'name' => $this->resolveEstablishmentName($user),
            ],
            'balance' => $balance,
            'summary' => $summary,
            'rows' => $rows->values(),
            'message' => $warnings !== [] ? implode(' ', $warnings) : null,
            'actions' => $this->buildActions(),
            'selected_period' => $selectedPeriod,
        ]);
    }

    /**
     * @return array{extra_headers: array{establishment_id: string}, filters?: string}
     */
    private function buildFilters(string $establishmentId, string $selectedPeriod): array
    {
        $filters = [
            'extra_headers' => [
                'establishment_id' => $establishmentId,
            ],
        ];

        try {
            $period = Carbon::createFromFormat('Y-m', $selectedPeriod);
        } catch (Throwable) {
            return $filters;
        }

        $filters['filters'] = json_encode([
            'created_at' => [
                'min' => $period->copy()->startOfMonth()->format('Y-m-d'),
                'max' => $period->copy()->endOfMonth()->format('Y-m-d'),
            ],
        ]);

        return $filters;
    }

    private function resolveSelectedPeriod(string $selectedPeriod): string
    {
        try {
            Carbon::createFromFormat('Y-m', $selectedPeriod);
        } catch (Throwable) {
            return Carbon::now()->format('Y-m');
        }

        return $selectedPeriod;
    }

    /**
     * @return array{available: int, available_label: string, blocked: int, blocked_label: string, total: int, total_label: string}
     */
    private function normalizeBalance(array $response): array
    {
        $available = $this->extractAmount($response, ['balance', 'data.balance']);
        $blocked = $this->extractAmount($response, ['blocked_balance', 'data.blocked_balance']);
        $total = $this->extractAmount($response, ['total_balance', 'data.total_balance']);

        if ($total === 0 && ($available !== 0 || $blocked !== 0)) {
            $total = $available + $blocked;
        }

        return [
            'available' => $available,
            'available_label' => $this->formatMoney($available),
            'blocked' => $blocked,
            'blocked_label' => $this->formatMoney($blocked),
            'total' => $total,
            'total_label' => $this->formatMoney($total),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function normalizeExtractRows(array $response): Collection
    {
        $items = data_get($response, 'data');

        if (! is_array($items)) {
            $items = data_get($response, 'items');
        }

        if (! is_array($items)) {
            $items = data_get($response, 'results');
        }

        return collect(is_array($items) ? $items : [])
            ->map(function (array $movement): array {
                $amount = $this->extractAmount($movement, ['amount', 'transaction_amount', 'value']);
                $modality = strtoupper((string) data_get($movement, 'modality', ''));
                $createdAt = $this->parseDate(data_get($movement, 'created_at') ?? data_get($movement, 'date'));
                $oldBalance = $this->extractAmount($movement, ['additionalInformation.old_balance', 'old_balance']);
                $currentBalance = $this->extractAmount($movement, ['additionalInformation.current_balance', 'current_balance']);
                $status = strtoupper((string) data_get($movement, 'status', ''));
                $type = strtoupper((string) data_get($movement, 'type', ''));

                return [
                    'id' => $this->resolveMovementId($movement),
                    'type' => $type,
                    'type_label' => $this->formatMovementType($type),
                    'modality' => $modality,
                    'modality_label' => $this->formatModality($modality),
                    'description' => $this->resolveDescription($movement),
                    'status' => $status,
                    'status_label' => $this->formatStatus($status),
                    'date' => $createdAt?->format('d/m/Y H:i') ?? 'Sem data',
                    'date_sort' => $createdAt?->getTimestamp() ?? 0,
                    'amount' => $amount,
                    'amount_label' => $this->formatMoney($amount),
                    'amount_signed_label' => $this->formatSignedMoney($amount, $modality),
                    'old_balance' => $oldBalance,
                    'old_balance_label' => $this->formatMoney($oldBalance),
                    'current_balance' => $currentBalance,
                    'current_balance_label' => $this->formatMoney($currentBalance),
                ];
            })
            ->sortByDesc('date_sort')
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{movements: int, incoming_total: int, incoming_total_label: string, outgoing_total: int, outgoing_total_label: string, balance_total_label: string}
     */
    private function buildSummary(array $balance, Collection $rows): array
    {
        $incomingTotal = (int) $rows->filter(function (array $row): bool {
            return ($row['modality'] ?? '') === 'IN';
        })->sum(function (array $row): int {
            return abs((int) ($row['amount'] ?? 0));
        });

        $outgoingTotal = (int) $rows->filter(function (array $row): bool {
            return ($row['modality'] ?? '') !== 'IN';
        })->sum(function (array $row): int {
            return abs((int) ($row['amount'] ?? 0));
        });

        return [
            'movements' => $rows->count(),
            'incoming_total' => $incomingTotal,
            'incoming_total_label' => $this->formatMoney($incomingTotal),
            'outgoing_total' => $outgoingTotal,
            'outgoing_total_label' => $this->formatMoney($outgoingTotal),
            'balance_total_label' => $balance['total_label'],
        ];
    }

    /**
     * @return array{available: int, available_label: string, blocked: int, blocked_label: string, total: int, total_label: string}
     */
    private function emptyBalance(): array
    {
        return [
            'available' => 0,
            'available_label' => $this->formatMoney(0),
            'blocked' => 0,
            'blocked_label' => $this->formatMoney(0),
            'total' => 0,
            'total_label' => $this->formatMoney(0),
        ];
    }

    /**
     * @return array{movements: int, incoming_total: int, incoming_total_label: string, outgoing_total: int, outgoing_total_label: string, balance_total_label: string}
     */
    private function emptySummary(): array
    {
        return [
            'movements' => 0,
            'incoming_total' => 0,
            'incoming_total_label' => $this->formatMoney(0),
            'outgoing_total' => 0,
            'outgoing_total_label' => $this->formatMoney(0),
            'balance_total_label' => $this->formatMoney(0),
        ];
    }

    /**
     * @return array<int, array{title: string, description: string, href: string}>
     */
    private function buildActions(): array
    {
        return [
            [
                'title' => 'Atualizar saldo',
                'description' => 'Recarrega os dados financeiros do parceiro.',
                'href' => '/cobranca/saldoextrato',
            ],
            [
                'title' => 'Abrir histórico',
                'description' => 'Volta para a listagem principal de cobranças.',
                'href' => '/cobranca',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $paths
     */
    private function extractAmount(array $payload, array $paths): int
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if (is_numeric($value)) {
                return (int) round((float) $value);
            }
        }

        return 0;
    }

    private function formatMoney(int $amountInCents): string
    {
        return 'R$ '.number_format($amountInCents / 100, 2, ',', '.');
    }

    private function formatSignedMoney(int $amountInCents, string $modality): string
    {
        $prefix = strtoupper($modality) === 'IN' ? '+' : '-';

        return $prefix.$this->formatMoney(abs($amountInCents));
    }

    private function formatModality(string $modality): string
    {
        return match ($modality) {
            'IN' => 'Entrada',
            'OUT' => 'Saída',
            default => $modality !== '' ? $modality : 'N/A',
        };
    }

    private function formatMovementType(string $type): string
    {
        return match ($type) {
            'PIX' => 'PIX',
            'P2P' => 'P2P',
            'FEES' => 'Tarifa',
            'TED' => 'TED',
            'BILLET' => 'Boleto',
            default => $type !== '' ? $type : 'Movimentação',
        };
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'PAID' => 'Pago',
            'APPROVED' => 'Aprovado',
            'PENDING' => 'Pendente',
            'PROCESSING' => 'Processando',
            'FAILED' => 'Falha',
            'CANCELED' => 'Cancelado',
            'REFUNDED' => 'Estornado',
            'COMPLETED' => 'Concluído',
            'CONFIRMED' => 'Confirmado',
            default => $status !== '' ? $status : 'N/A',
        };
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $movement
     */
    private function resolveMovementId(array $movement): string
    {
        $id = data_get($movement, '_id')
            ?? data_get($movement, 'id')
            ?? data_get($movement, 'transaction_id')
            ?? data_get($movement, 'external_id');

        return is_scalar($id) ? (string) $id : '';
    }

    /**
     * @param  array<string, mixed>  $movement
     */
    private function resolveDescription(array $movement): string
    {
        $description = data_get($movement, 'description')
            ?? data_get($movement, 'title')
            ?? data_get($movement, 'reason')
            ?? data_get($movement, 'additionalInformation.description')
            ?? '';

        return is_string($description) && trim($description) !== '' ? $description : 'Movimentação financeira';
    }

    private function resolveEstablishmentName(mixed $user): string
    {
        $establishment = $user->vendedor?->estabelecimento;

        return (string) data_get(
            $establishment,
            'display_name',
            data_get($establishment, 'fantasy_name', data_get($establishment, 'name', 'Estabelecimento'))
        );
    }
}
