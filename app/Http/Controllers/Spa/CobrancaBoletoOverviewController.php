<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Services\BoletoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CobrancaBoletoOverviewController extends Controller
{
    public function __construct(
        private readonly BoletoService $boletoService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('vendedor');

        $establishmentId = $user->getEstabelecimentoId();
        $selectedPeriod = $this->resolveSelectedPeriod($request->string('period')->toString());
        $filters = $this->buildFilters($establishmentId, $selectedPeriod);
        $response = $this->boletoService->listarBoletos($filters);

        $boletos = $this->extractBoletos($response)
            ->filter(fn (array $boleto): bool => $this->matchesEstablishment($boleto, $establishmentId))
            ->values();

        $rows = $boletos->map(function (array $boleto): array {
            $status = $this->normalizeStatus($boleto['status'] ?? null);
            $createdAt = $this->parseDate($boleto['created_at'] ?? $boleto['updated_at'] ?? null);
            $customerName = $this->resolveCustomerName($boleto);

            return [
                'kind' => 'boleto',
                'id' => $this->resolveBoletoId($boleto),
                'code' => $this->resolveBoletoId($boleto),
                'type' => 'Boleto',
                'title' => $customerName !== '' ? $customerName : 'Boleto '.$this->resolveBoletoId($boleto),
                'description' => $this->resolveDescription($boleto, $customerName),
                'status' => $this->formatStatus($status),
                'raw_status' => $status,
                'amount' => $this->formatMoney((int) ($boleto['amount'] ?? 0)),
                'fee' => $this->formatMoney((int) ($boleto['fees'] ?? 0)),
                'customer' => $customerName !== '' ? $customerName : 'Cliente',
                'establishment' => $this->resolveEstablishmentName($boleto),
                'created_at_sort' => $createdAt?->getTimestamp() ?? 0,
                'created_at' => $createdAt?->format('d/m/Y H:i') ?? 'Sem data',
                'detail_href' => '/cobranca/boleto/'.$this->resolveBoletoId($boleto),
            ];
        })->sortByDesc('created_at_sort')->values();

        $summary = [
            'total_billets' => $boletos->count(),
            'paid_billets' => $boletos->filter(fn (array $boleto): bool => in_array($this->normalizeStatus($boleto['status'] ?? null), ['PAID', 'APPROVED'], true))->count(),
            'pending_billets' => $boletos->filter(fn (array $boleto): bool => in_array($this->normalizeStatus($boleto['status'] ?? null), ['PENDING', 'PROCESSING'], true))->count(),
            'failed_billets' => $boletos->filter(fn (array $boleto): bool => in_array($this->normalizeStatus($boleto['status'] ?? null), ['FAILED', 'CANCELED', 'REFUNDED'], true))->count(),
            'total_amount' => $this->formatMoney((int) $boletos->sum('amount')),
            'total_fees' => $this->formatMoney((int) $boletos->sum('fees')),
        ];

        $periods = $this->buildPeriodOptions($boletos);

        return response()->json([
            'seller_name' => $user->name ?: 'Vendedor',
            'summary' => $summary,
            'periods' => $periods,
            'selected_period' => $selectedPeriod,
            'rows' => $rows,
            'recent_boletos' => $rows->take(5)->values(),
            'actions' => [
                ['title' => 'Criar boleto', 'description' => 'Emissão e acompanhamento.', 'href' => '/cobranca/boleto'],
                ['title' => 'Ver histórico', 'description' => 'Acesse o histórico geral.', 'href' => '/cobranca'],
            ],
        ]);
    }

    private function buildFilters(string|int|null $establishmentId, string $selectedPeriod): array
    {
        $filters = [];

        if ($establishmentId !== null && (string) $establishmentId !== '') {
            $filters['establishment.id'] = $establishmentId;
        }

        if ($selectedPeriod !== 'all') {
            $period = Carbon::createFromFormat('Y-m', $selectedPeriod);

            $filters['created_at'] = [
                'min' => $period->copy()->startOfMonth()->format('Y-m-d'),
                'max' => $period->copy()->endOfMonth()->format('Y-m-d'),
            ];
        }

        return [
            'perPage' => 1000,
            'page' => 1,
            'filters' => json_encode($filters),
        ];
    }

    private function buildPeriodOptions($boletos): array
    {
        $currentPeriod = Carbon::now()->format('Y-m');
        $availablePeriods = collect($boletos)
            ->map(function (array $boleto): string {
                return $this->parseDate($boleto['created_at'] ?? $boleto['updated_at'] ?? null)?->format('Y-m')
                    ?? Carbon::now()->format('Y-m');
            })
            ->unique()
            ->sortDesc()
            ->values();

        return collect([$currentPeriod])
            ->merge($availablePeriods)
            ->unique()
            ->map(function (string $period): array {
                return [
                    'label' => Carbon::createFromFormat('Y-m', $period)->format('m/Y'),
                    'value' => $period,
                ];
            })
            ->prepend([
                'label' => 'Todos os meses',
                'value' => 'all',
            ])
            ->values()
            ->all();
    }

    private function resolveSelectedPeriod(string $selectedPeriod): string
    {
        if ($selectedPeriod === 'all') {
            return 'all';
        }

        try {
            Carbon::createFromFormat('Y-m', $selectedPeriod);
        } catch (\Throwable) {
            return Carbon::now()->format('Y-m');
        }

        return $selectedPeriod;
    }

    private function matchesEstablishment(array $boleto, string|int|null $establishmentId): bool
    {
        if ($establishmentId === null || (string) $establishmentId === '') {
            return true;
        }

        $boletoEstablishmentId = data_get($boleto, 'establishment.id')
            ?? data_get($boleto, 'establishment_id')
            ?? data_get($boleto, 'extra_headers.establishment_id');

        return (string) $boletoEstablishmentId === (string) $establishmentId;
    }

    private function resolveBoletoId(array $boleto): string
    {
        $id = $boleto['_id']
            ?? $boleto['id']
            ?? $boleto['external_id']
            ?? data_get($boleto, 'boleto._id')
            ?? data_get($boleto, 'boleto.id')
            ?? data_get($boleto, 'boleto.external_id');

        return is_scalar($id) ? (string) $id : '';
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function extractBoletos(array $response): Collection
    {
        $items = data_get($response, 'data');

        if (! is_array($items)) {
            $items = data_get($response, 'items');
        }

        if (! is_array($items)) {
            $items = data_get($response, 'results');
        }

        return collect(is_array($items) ? $items : []);
    }

    private function resolveDescription(array $boleto, string $customerName): string
    {
        $description = data_get($boleto, 'description')
            ?? data_get($boleto, 'title')
            ?? data_get($boleto, 'client.email')
            ?? data_get($boleto, 'customer_document')
            ?? '';

        if (is_string($description) && trim($description) !== '') {
            return $description;
        }

        return $customerName !== '' ? $customerName : 'Boleto bancário';
    }

    private function resolveEstablishmentName(array $boleto): string
    {
        $name = data_get($boleto, 'establishment.name')
            ?? data_get($boleto, 'establishment.display_name')
            ?? data_get($boleto, 'establishment.fantasy_name')
            ?? data_get($boleto, 'gateway_key');

        return is_string($name) && trim($name) !== '' ? $name : 'Estabelecimento';
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatMoney(int $amountInCents): string
    {
        return 'R$ '.number_format($amountInCents / 100, 2, ',', '.');
    }

    private function formatStatus(?string $status): string
    {
        return match ($this->normalizeStatus($status)) {
            'PAID' => 'Pago',
            'APPROVED' => 'Aprovado',
            'PENDING' => 'Pendente',
            'PROCESSING' => 'Processando',
            'FAILED' => 'Falha',
            'CANCELED' => 'Cancelado',
            'REFUNDED' => 'Estornado',
            default => $status ?? 'Desconhecido',
        };
    }

    private function normalizeStatus(?string $status): ?string
    {
        if (! is_string($status) || trim($status) === '') {
            return null;
        }

        return strtoupper($status);
    }

    private function resolveCustomerName(array $boleto): string
    {
        $customerName = trim((string) data_get($boleto, 'client.name', data_get($boleto, 'customer_name', '')));

        if ($customerName !== '') {
            return $customerName;
        }

        $firstName = trim((string) data_get($boleto, 'client.first_name', data_get($boleto, 'customer.first_name', '')));
        $lastName = trim((string) data_get($boleto, 'client.last_name', data_get($boleto, 'customer.last_name', '')));

        return trim($firstName.' '.$lastName);
    }
}
