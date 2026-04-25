<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Models\PaytimeEstablishment;
use App\Models\PaytimeTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendedoresOverviewController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $users = User::query()
            ->with(['vendedor'])
            ->where('nivel_acesso', 'vendedor')
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        $establishmentIds = $users
            ->pluck('vendedor.estabelecimento_id')
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();

        $establishments = PaytimeEstablishment::query()
            ->whereIn('id', $establishmentIds)
            ->get()
            ->keyBy('id');

        $transactionsByEstablishment = PaytimeTransaction::query()
            ->selectRaw('establishment_id, COUNT(*) as total_transactions, SUM(amount) as total_amount, MAX(created_at) as last_transaction')
            ->whereIn('establishment_id', $establishmentIds)
            ->groupBy('establishment_id')
            ->get()
            ->keyBy('establishment_id');

        $rows = $users->map(function (User $user) use ($establishments, $transactionsByEstablishment): array {
            $vendor = $user->vendedor;
            $establishment = $vendor?->estabelecimento_id !== null
                ? $establishments->get((int) $vendor->estabelecimento_id)
                : null;
            $stats = $vendor?->estabelecimento_id !== null
                ? $transactionsByEstablishment->get((string) $vendor->estabelecimento_id)
                : null;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $vendor?->status === 'ativo' ? 'Ativo' : 'Inativo',
                'role' => $vendor?->sub_nivel === 'admin_loja' ? 'Admin de loja' : 'Vendedor de loja',
                'establishment' => $establishment?->display_name ?? $vendor?->estabelecimento_id ?? 'Sem vínculo',
                'establishment_id' => $vendor?->estabelecimento_id,
                'commission' => $vendor?->comissao_formatada ?? 'N/A',
                'goal' => $vendor?->meta_vendas_formatada ?? 'N/A',
                'must_change_password' => (bool) ($vendor?->must_change_password ?? false),
                'phone' => $vendor?->telefone ?? 'N/A',
                'total_transactions' => (int) ($stats?->total_transactions ?? 0),
                'total_amount' => $this->formatMoney((int) ($stats?->total_amount ?? 0)),
                'last_activity' => $stats?->last_transaction ? Carbon::parse($stats->last_transaction)->format('d/m/Y H:i') : 'Sem atividade',
            ];
        });

        $summary = [
            'total_vendors' => $rows->count(),
            'active_vendors' => $rows->where('status', 'Ativo')->count(),
            'inactive_vendors' => $rows->where('status', 'Inativo')->count(),
            'admin_loja' => $rows->where('role', 'Admin de loja')->count(),
            'vendedor_loja' => $rows->where('role', 'Vendedor de loja')->count(),
            'must_change_password' => $rows->where('must_change_password', true)->count(),
            'linked_establishments' => count($establishmentIds),
        ];

        $selected = $rows->first() ?? [
            'id' => null,
            'name' => 'Sem dados',
            'email' => 'N/A',
            'status' => 'Inativo',
            'role' => 'Sem vínculo',
            'establishment' => 'Sem vínculo',
            'establishment_id' => null,
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
            'actions' => [
                ['title' => 'Novo acesso', 'description' => 'Criar conta de vendedor.', 'href' => '/vendedores/acesso'],
                ['title' => 'Faturamento', 'description' => 'Ver desempenho por loja.', 'href' => '/vendedores/faturamento'],
            ],
            'rows' => $rows->values(),
            'selected' => $selected,
            'recent_activity' => $rows->take(5)->values(),
        ]);
    }

    private function formatMoney(int $amountInCents): string
    {
        return 'R$ '.number_format($amountInCents / 100, 2, ',', '.');
    }
}
