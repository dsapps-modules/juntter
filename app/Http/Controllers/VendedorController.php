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

        // Buscar lista de estabelecimentos na API (Fonte de verdade)
        try {
            $response = $this->estabelecimentoService->listarEstabelecimentos();
            $estabelecimentos = $response['data'] ?? []; // Ajuste conforme estrutura real de resposta da API
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('VendedorController: Erro API', ['msg' => $e->getMessage()]);
            $estabelecimentos = [];
        }

        $dados = [];

        foreach ($estabelecimentos as $est) {
            // A API retorna id como inteiro e nome em first_name/last_name
            $estId = $est['id'] ?? ($est['_id'] ?? null);

            // Construir nome completo
            $firstName = $est['first_name'] ?? '';
            $lastName = $est['last_name'] ?? '';
            $nomeFantasia = trim("$firstName $lastName");

            // Se não tiver nome, tentar email ou documento
            if (empty($nomeFantasia)) {
                $nomeFantasia = $est['email'] ?? ($est['document'] ?? 'Sem Nome');
            }

            if (!$estId)
                continue;

            // Agregar dados do estabelecimento neste mês (Banco Local)
            $stats = PaytimeTransaction::where('establishment_id', (string) $estId)
                ->whereBetween('created_at', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59'])
                ->selectRaw('
                    SUM(amount) as total_liquido,
                    SUM(original_amount) as total_bruto,
                    SUM(fees) as total_taxas,
                    COUNT(*) as qtd
                ')
                ->first();

            $dados[] = [
                'nome' => $nomeFantasia,
                'estabelecimento_id' => $estId,
                'total_liquido' => $stats->total_liquido ?? 0,
                'total_bruto' => $stats->total_bruto ?? 0,
                'total_taxas' => $stats->total_taxas ?? 0,
                'qtd' => $stats->qtd ?? 0,
            ];
        }

        // Ordenar por faturamento liquido decrescente
        usort($dados, function ($a, $b) {
            return $b['total_liquido'] <=> $a['total_liquido'];
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

        // IDs de estabelecimentos já cadastrados
        $estabelecimentosCadastradosIds = $usuariosLocais->pluck('vendedor.estabelecimento_id')->toArray();

        // 2. Buscar lista de estabelecimentos na API
        try {
            $response = $this->estabelecimentoService->listarEstabelecimentos();
            $todosEstabelecimentos = $response['data'] ?? [];
        } catch (\Exception $e) {
            $todosEstabelecimentos = [];
            session()->flash('error', 'Erro ao carregar lista de estabelecimentos da API.');
        }

        // 3. Filtrar apenas os disponíveis (que não estão cadastrados localmente) e formatar
        $disponiveis = [];
        foreach ($todosEstabelecimentos as $est) {
            $estId = $est['id'] ?? ($est['_id'] ?? null);
            if (!$estId)
                continue;

            // Se já cadastrado, pular
            if (in_array((string) $estId, $estabelecimentosCadastradosIds))
                continue;

            $firstName = $est['first_name'] ?? '';
            $lastName = $est['last_name'] ?? '';
            $nome = trim("$firstName $lastName");
            if (empty($nome))
                $nome = $est['fantasy_name'] ?? 'Sem Nome';

            $email = $est['email'] ?? '';

            $disponiveis[] = [
                'id' => $estId,
                'name' => $nome,
                'email' => $email
            ];
        }

        // Ordenar por nome
        usort($disponiveis, fn($a, $b) => strcmp($a['name'], $b['name']));

        // Breadcrumb
        $breadcrumbItems = [
            ['label' => 'Vendedores', 'url' => '#'],
            ['label' => 'Acesso', 'url' => route('vendedores.acesso')],
        ];

        return view('vendedores.acesso', compact('disponiveis', 'usuariosLocais', 'breadcrumbItems'));
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
