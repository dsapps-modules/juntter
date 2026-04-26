<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Models\PaytimeEstablishment;
use App\Models\PaytimeTransaction;
use App\Models\Vendedor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class VendedoresOverviewController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $searchTerm = trim((string) $request->input('search', ''));
        $filter = (string) $request->input('filter', 'Todos');
        $page = max(1, (int) $request->integer('page', 1));
        $perPage = 20;

        $summary = $this->buildSummary();

        $paginatedVendors = $this->buildFilteredQuery($searchTerm, $filter)
            ->with(['user', 'estabelecimento'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $rows = $this->mapRows($paginatedVendors);
        $establishmentIds = $rows
            ->pluck('establishment_id')
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();

        $establishments = PaytimeEstablishment::query()
            ->withTrashed()
            ->whereIn('id', $establishmentIds)
            ->get()
            ->keyBy('id');

        $transactionsByEstablishment = PaytimeTransaction::query()
            ->selectRaw('establishment_id, COUNT(*) as total_transactions, SUM(amount) as total_amount, MAX(created_at) as last_transaction')
            ->whereIn('establishment_id', $establishmentIds)
            ->groupBy('establishment_id')
            ->get()
            ->keyBy('establishment_id');

        $rows = $rows->map(function (array $row) use ($establishments, $transactionsByEstablishment): array {
            $establishment = $row['establishment_id'] !== null
                ? $establishments->get((int) $row['establishment_id'])
                : null;
            $stats = $row['establishment_id'] !== null
                ? $transactionsByEstablishment->get((string) $row['establishment_id'])
                : null;

            $row['establishment'] = $establishment?->display_name ?? 'Sem vínculo';
            $row['document'] = $establishment?->document ?? 'N/A';
            $row['location'] = $this->formatLocation($establishment, $row['location']);
            $row['total_transactions'] = (int) ($stats?->total_transactions ?? 0);
            $row['total_amount'] = $this->formatMoney((int) ($stats?->total_amount ?? 0));
            $row['last_activity'] = $stats?->last_transaction
                ? Carbon::parse($stats->last_transaction)->format('d/m/Y H:i')
                : 'Sem atividade';

            return $row;
        });

        $selected = $rows->first() ?? [
            'id' => null,
            'name' => 'Sem dados',
            'email' => 'N/A',
            'status' => 'Inativo',
            'role' => 'Sem vínculo',
            'establishment' => 'Sem vínculo',
            'establishment_id' => null,
            'document' => 'N/A',
            'location' => 'N/A',
            'commission' => 'N/A',
            'goal' => 'N/A',
            'must_change_password' => false,
            'phone' => 'N/A',
            'total_transactions' => 0,
            'total_amount' => $this->formatMoney(0),
            'last_activity' => 'Sem atividade',
        ];

        return response()->json([
            'summary' => $summary,
            'filters' => ['Todos', 'Ativos', 'Inativos', 'Senha obrigatória'],
            'pagination' => [
                'current_page' => $paginatedVendors->currentPage(),
                'per_page' => $paginatedVendors->perPage(),
                'total' => $paginatedVendors->total(),
                'last_page' => $paginatedVendors->lastPage(),
            ],
            'actions' => [
                ['title' => 'Novo acesso', 'description' => 'Criar conta de vendedor.', 'href' => '/vendedores/acesso'],
                ['title' => 'Faturamento', 'description' => 'Ver desempenho por loja.', 'href' => '/vendedores/faturamento'],
            ],
            'rows' => $rows->values(),
            'selected' => $selected,
            'recent_activity' => $rows->take(5)->values(),
        ]);
    }

    private function buildSummary(): array
    {
        $query = Vendedor::query();

        return [
            'total_vendors' => (clone $query)->count(),
            'active_vendors' => (clone $query)->where('status', 'ativo')->count(),
            'inactive_vendors' => (clone $query)->where('status', 'inativo')->count(),
            'admin_loja' => (clone $query)->where('sub_nivel', 'admin_loja')->count(),
            'vendedor_loja' => (clone $query)->where('sub_nivel', 'vendedor_loja')->count(),
            'must_change_password' => (clone $query)->where('must_change_password', true)->count(),
            'linked_establishments' => (clone $query)
                ->whereNotNull('estabelecimento_id')
                ->distinct()
                ->count('estabelecimento_id'),
        ];
    }

    private function buildFilteredQuery(string $searchTerm, string $filter): Builder
    {
        return Vendedor::query()
            ->when($searchTerm !== '', function ($query) use ($searchTerm): void {
                $query->where(function ($builder) use ($searchTerm): void {
                    $like = "%{$searchTerm}%";

                    $builder->where('estabelecimento_id', 'like', $like)
                        ->orWhere('sub_nivel', 'like', $like)
                        ->orWhere('status', 'like', $like)
                        ->orWhere('telefone', 'like', $like)
                        ->orWhere('endereco', 'like', $like)
                        ->orWhereHas('user', function ($userQuery) use ($like): void {
                            $userQuery->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        })
                        ->orWhereHas('estabelecimento', function ($establishmentQuery) use ($like): void {
                            $establishmentQuery->where('fantasy_name', 'like', $like)
                                ->orWhere('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('document', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });
                });
            })
            ->when($filter === 'Ativos', function ($query): void {
                $query->where('status', 'ativo');
            })
            ->when($filter === 'Inativos', function ($query): void {
                $query->where('status', 'inativo');
            })
            ->when($filter === 'Senha obrigatória', function ($query): void {
                $query->where('must_change_password', true);
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function mapRows(LengthAwarePaginator $paginator): Collection
    {
        return collect($paginator->items())->map(function (Vendedor $vendor): array {
            $establishment = $vendor->estabelecimento;
            $user = $vendor->user;

            return [
                'id' => $vendor->id,
                'name' => $user?->name ?? 'Sem nome',
                'email' => $user?->email ?? 'N/A',
                'status' => $vendor->status === 'ativo' ? 'Ativo' : 'Inativo',
                'role' => $vendor->sub_nivel === 'admin_loja' ? 'Admin de loja' : 'Vendedor de loja',
                'establishment' => $establishment?->display_name ?? 'Sem vínculo',
                'establishment_id' => $vendor->estabelecimento_id,
                'document' => $establishment?->document ?? 'N/A',
                'location' => $this->formatLocation($establishment, $vendor->endereco),
                'commission' => $vendor->comissao_formatada,
                'goal' => $vendor->meta_vendas_formatada,
                'must_change_password' => (bool) $vendor->must_change_password,
                'phone' => $vendor->telefone ?? 'N/A',
            ];
        });
    }

    private function formatMoney(int $amountInCents): string
    {
        return 'R$ '.number_format($amountInCents / 100, 2, ',', '.');
    }

    private function formatLocation(?PaytimeEstablishment $establishment, ?string $fallbackAddress): string
    {
        if ($establishment !== null && is_array($establishment->address_json)) {
            $city = $establishment->address_json['city'] ?? null;
            $state = $establishment->address_json['state'] ?? null;

            if ($city && $state) {
                return "{$city} - {$state}";
            }
        }

        if ($fallbackAddress === null || trim($fallbackAddress) === '') {
            return 'N/A';
        }

        $decodedAddress = json_decode($fallbackAddress, true);

        if (is_array($decodedAddress)) {
            $city = $decodedAddress['city'] ?? null;
            $state = $decodedAddress['state'] ?? null;

            if ($city && $state) {
                return "{$city} - {$state}";
            }

            $parts = array_filter([
                $decodedAddress['street'] ?? null,
                $decodedAddress['number'] ?? null,
                $decodedAddress['neighborhood'] ?? null,
                $decodedAddress['city'] ?? null,
                $decodedAddress['state'] ?? null,
            ]);

            if (! empty($parts)) {
                return implode(' ', $parts);
            }
        }

        return $fallbackAddress;
    }
}
