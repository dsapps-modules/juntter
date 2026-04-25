<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Models\PaytimeTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendedorFaturamentoOverviewController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $mes = (int) $request->integer('mes', now()->month);
        $ano = (int) $request->integer('ano', now()->year);

        if ($mes < 1 || $mes > 12) {
            $mes = now()->month;
        }

        if ($ano < 2000) {
            $ano = now()->year;
        }

        $dataInicio = Carbon::create($ano, $mes, 1)->startOfMonth();
        $dataFim = Carbon::create($ano, $mes, 1)->endOfMonth();

        $transacoesAgrupadas = PaytimeTransaction::query()
            ->select(
                'establishment_id',
                DB::raw('SUM(amount) as total_liquido'),
                DB::raw('SUM(original_amount) as total_bruto'),
                DB::raw('SUM(fees) as total_taxas'),
                DB::raw('COUNT(id) as qtd')
            )
            ->whereBetween('created_at', [$dataInicio, $dataFim])
            ->groupBy('establishment_id');

        $rows = DB::table('paytime_establishments as e')
            ->joinSub($transacoesAgrupadas, 't', function ($join): void {
                $join->on('e.id', '=', 't.establishment_id');
            })
            ->select(
                'e.id as estabelecimento_id',
                'e.fantasy_name',
                'e.first_name',
                'e.last_name',
                'e.email',
                'e.document',
                't.total_liquido',
                't.total_bruto',
                't.total_taxas',
                't.qtd'
            )
            ->where('t.total_liquido', '>', 0)
            ->orderByDesc('t.total_liquido')
            ->get()
            ->map(function ($item): array {
                $nomeFantasia = $item->fantasy_name;

                if (empty($nomeFantasia)) {
                    $nomeFantasia = trim("{$item->first_name} {$item->last_name}");
                }

                if (empty($nomeFantasia)) {
                    $nomeFantasia = $item->email ?? ($item->document ?? 'Sem nome');
                }

                return [
                    'nome' => $nomeFantasia,
                    'estabelecimento_id' => $item->estabelecimento_id,
                    'total_liquido' => (int) $item->total_liquido,
                    'total_bruto' => (int) $item->total_bruto,
                    'total_taxas' => (int) $item->total_taxas,
                    'qtd' => (int) $item->qtd,
                ];
            });

        $summary = [
            'total_registros' => $rows->count(),
            'total_bruto' => (int) $rows->sum('total_bruto'),
            'total_taxas' => (int) $rows->sum('total_taxas'),
            'total_liquido' => (int) $rows->sum('total_liquido'),
            'transacoes' => (int) $rows->sum('qtd'),
        ];

        return response()->json([
            'period' => [
                'mes' => $mes,
                'ano' => $ano,
                'label' => $this->formatPeriodLabel($mes, $ano),
            ],
            'summary' => $summary,
            'rows' => $rows->values(),
            'actions' => [
                ['title' => 'Novo acesso', 'description' => 'Criar conta de vendedor.', 'href' => '/vendedores/acesso'],
                ['title' => 'Visão geral', 'description' => 'Voltar para a página principal.', 'href' => '/vendedores'],
            ],
        ]);
    }

    private function formatPeriodLabel(int $mes, int $ano): string
    {
        $months = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];

        return ($months[$mes] ?? 'Mês').' de '.$ano;
    }
}
