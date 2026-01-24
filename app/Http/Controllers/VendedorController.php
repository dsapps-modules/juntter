<?php

namespace App\Http\Controllers;

use App\Models\PaytimeTransaction;
use App\Models\Vendedor;
use Illuminate\Http\Request;
use DateTime;
use Illuminate\Support\Facades\DB;
use App\Services\EstabelecimentoService;

class VendedorController extends Controller
{
    protected $estabelecimentoService;

    public function __construct(EstabelecimentoService $estabelecimentoService)
    {
        $this->estabelecimentoService = $estabelecimentoService;
    }

    /**
     * Exibe o faturamento por vendedor (Admin)
     */
    public function faturamento(Request $request)
    {
        $mes = $request->input('mes') ?? date('m');
        $ano = $request->input('ano') ?? date('Y');

        $dataInicio = "$ano-$mes-01";
        $dataFim = date('Y-m-t', strtotime($dataInicio));

        // Consulta otimizada: Agrupa transações primeiro, depois faz o JOIN com estabelecimentos
        $transacoesAgrupadas = DB::table('paytime_transactions as t')
            ->select(
                't.establishment_id',
                DB::raw('SUM(t.amount) as total_liquido'),
                DB::raw('SUM(t.original_amount) as total_bruto'),
                DB::raw('SUM(t.fees) as total_taxas'),
                DB::raw('COUNT(t.id) as qtd')
            )
            ->whereBetween('t.created_at', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59'])
            ->groupBy('t.establishment_id');

        $resultados = DB::table('paytime_establishments as e')
            ->joinSub($transacoesAgrupadas, 't', function ($join) {
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
            ->get();

        // Mapear para o formato da view
        $dados = $resultados->map(function ($item) {
            // Lógica de nome similar ao anterior
            $nomeFantasia = $item->fantasy_name;
            if (empty($nomeFantasia)) {
                $nomeFantasia = trim("{$item->first_name} {$item->last_name}");
            }
            if (empty($nomeFantasia)) {
                $nomeFantasia = $item->email ?? ($item->document ?? 'Sem Nome');
            }

            return [
                'nome' => $nomeFantasia,
                'estabelecimento_id' => $item->estabelecimento_id,
                'total_liquido' => $item->total_liquido,
                'total_bruto' => $item->total_bruto,
                'total_taxas' => $item->total_taxas,
                'qtd' => $item->qtd,
            ];
        });

        // Breadcrumb
        $breadcrumbItems = [
            ['label' => 'Vendedores', 'url' => '#'],
            ['label' => 'Faturamento', 'url' => route('vendedores.faturamento')],
        ];

        return view('vendedores.faturamento', compact('dados', 'mes', 'ano', 'breadcrumbItems'));
    }



    /**
     * Exibe a página de gestão de acesso
     */
    public function acesso()
    {
        // 1. Buscar todos os vendedores locais (Users com role 'vendedor' e seus dados de estabelecimento)
        $usuariosLocais = \App\Models\User::where('nivel_acesso', 'vendedor')
            ->with('vendedor') // Eager load success relationship
            ->get();

        // Breadcrumb
        $breadcrumbItems = [
            ['label' => 'Vendedores', 'url' => '#'],
            ['label' => 'Acesso', 'url' => route('vendedores.acesso')],
        ];

        return view('vendedores.acesso', compact('usuariosLocais', 'breadcrumbItems'));
    }

    /**
     * Busca estabelecimentos disponíveis para vinculação (Select2 AJAX)
     */
    public function searchEstabelecimentosAvailable(Request $request)
    {
        $term = $request->get('q');

        // IDs já em uso
        $usedIds = \App\Models\User::where('nivel_acesso', 'vendedor')
            ->with('vendedor')
            ->get()
            ->pluck('vendedor.estabelecimento_id')
            ->filter()
            ->toArray();

        $query = \App\Models\PaytimeEstablishment::whereNotIn('id', $usedIds);

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('fantasy_name', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('document', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $results = $query->limit(20)->get()->map(function ($item) {
            $nome = $item->display_name;
            if (empty($nome) || $nome === 'Sem Nome') {
                $nome = $item->document ?? $item->email;
            }
            return [
                'id' => $item->id,
                'text' => $nome . " (ID: {$item->id})",
                'email' => $item->email,
                'name_clean' => $nome
            ];
        });

        return response()->json(['results' => $results]);
    }

    /**
     * Cria um novo acesso de vendedor
     */
    public function storeAcesso(Request $request)
    {
        $request->validate([
            'establishment_id' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        DB::beginTransaction();
        try {
            // Criar Usuário
            $user = \App\Models\User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                'nivel_acesso' => 'vendedor',
                'email_verified_at' => now(), // Auto verify for simplicity per workflow
            ]);

            // Criar Registro de Vendedor vinculado
            $user->vendedor()->create([
                'estabelecimento_id' => $request->establishment_id,
                'sub_nivel' => 'admin_loja',
                'status' => 'ativo',
                // Outros campos nullables
            ]);

            DB::commit();
            return redirect()->route('vendedores.acesso')->with('success', 'Acesso criado com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao criar acesso: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Atualiza dados básicos do vendedor (Nome e Email)
     */
    public function updateAcesso(Request $request, $id)
    {
        $user = \App\Models\User::findOrFail($id);

        if (!$user->isVendedor()) {
            return back()->with('error', 'Ação não permitida para este usuário.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->route('vendedores.acesso')->with('success', 'Dados do vendedor atualizados com sucesso.');
    }

    /**
     * Atualiza senha do vendedor
     */
    public function updateSenha(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = \App\Models\User::findOrFail($id);

        // Security check: ensure it is a vendor
        if (!$user->isVendedor()) {
            return back()->with('error', 'Usuário inválido.');
        }

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password)
        ]);

        return redirect()->route('vendedores.acesso')->with('success', 'Senha atualizada com sucesso.');
    }

    /**
     * Remove acesso do vendedor
     */
    public function destroyAcesso($id)
    {
        $user = \App\Models\User::findOrFail($id);

        if (!$user->isVendedor()) {
            return back()->with('error', 'Ação não permitida para este usuário.');
        }

        $user->delete(); // Cascade will delete vendedor record

        return redirect()->route('vendedores.acesso')->with('success', 'Acesso removido com sucesso.');
    }
}
