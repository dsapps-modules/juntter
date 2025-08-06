<?php

namespace App\Http\Controllers;

use App\Services\EstabelecimentoService;
use App\Services\SplitPreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EstabelecimentoController extends Controller
{
    protected $estabelecimentoService;
    protected $splitPreService;

    public function __construct(EstabelecimentoService $estabelecimentoService, SplitPreService $splitPreService)
    {
        $this->estabelecimentoService = $estabelecimentoService;
        $this->splitPreService = $splitPreService;
    }

    public function show($id)
    {
        try {
            $estabelecimento = $this->estabelecimentoService->buscarEstabelecimento($id);
            
            // Buscar regras de split pré
            $regrasSplit = $this->splitPreService->listarRegrasSplitPre($id);
            
            // Buscar lista de estabelecimentos para o select
            $estabelecimentos = $this->estabelecimentoService->listarEstabelecimentos();
            
            return view('estabelecimentos.show', compact('estabelecimento', 'regrasSplit', 'estabelecimentos'));
        } catch (\Exception $e) {
            Log::error('Erro ao buscar estabelecimento: ' . $e->getMessage());
            return redirect()->route('admin.dashboard')->with('error', 'Erro ao carregar dados do estabelecimento.');
        }
    }

    public function edit($id)
    {
        try {
            $estabelecimento = $this->estabelecimentoService->buscarEstabelecimento($id);
            return view('estabelecimentos.edit', compact('estabelecimento'));
        } catch (\Exception $e) {
            Log::error('Erro ao buscar estabelecimento para edição: ' . $e->getMessage());
            return redirect()->route('admin.dashboard')->with('error', 'Erro ao carregar dados do estabelecimento.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $dados = $request->validate([
                'access_type' => 'required|in:ACQUIRER,BANKING',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'phone_number' => 'required|string|max:20',
                'revenue' => 'required|numeric',
                'format' => 'required|in:SS,SC,SPE,LTDA,SA,ME,MEI,EI,EIRELI,SLU,ESI',
                'email' => 'required|email',
                'gmv' => 'nullable|numeric',
                'birthdate' => 'required|date_format:Y-m-d',
            ]);

            // Converter revenue e gmv para números
            $dados['revenue'] = (float) $dados['revenue'];
            if (!empty($dados['gmv'])) {
                $dados['gmv'] = (float) $dados['gmv'];
            } else {
                unset($dados['gmv']); // Remove se estiver vazio
            }

            $resultado = $this->estabelecimentoService->atualizarEstabelecimento($id, $dados);
            
            return redirect()->route('estabelecimentos.show', $id)
                           ->with('success', 'Estabelecimento atualizado com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar estabelecimento: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erro ao atualizar estabelecimento.');
        }
    }

    public function criarRegraSplit(Request $request, $id)
    {
        try {
            $dados = $request->validate([
                'title' => 'required|string|max:255',
                'modality' => 'required|in:ALL,CREDIT,DEBIT,PIX',
                'channel' => 'required|in:ALL,CHIP,TAP,SMART,ONLINE',
                'division' => 'required|in:PERCENTAGE,CURRENCY',
                'active' => 'nullable', 
                'installment' => 'nullable|integer|min:1|max:12',
                'establishments' => 'required|array|min:1',
                'establishments.*.id' => 'required|integer',
                'establishments.*.value' => 'required|integer|min:1',
                'establishments.*.active' => 'nullable', 
            ]);

            // Tratar dados após validação
            $dados = $this->tratarDadosSplit($dados);
            
            $resultado = $this->splitPreService->criarRegraSplitPre($id, $dados);
            
            return redirect()->route('estabelecimentos.show', $id)
                           ->with('success', 'Regra de split criada com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao criar regra de split: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erro ao criar regra de split: ' . $e->getMessage());
        }
    }

    /**
     * Trata os dados de split para garantir tipos corretos
     */
    private function tratarDadosSplit(array $dados): array
    {
        // Tratar campo active
        $dados['active'] = isset($dados['active']) ? true : false;
        
        // Tratar installment
        if (empty($dados['installment'])) {
            unset($dados['installment']);
        }
        
        // Tratar establishments
        foreach ($dados['establishments'] as $key => $establishment) {
            $dados['establishments'][$key]['active'] = isset($establishment['active']) ? true : false;
            $dados['establishments'][$key]['id'] = (int) $establishment['id'];
            $dados['establishments'][$key]['value'] = (int) $establishment['value'];
        }
        
        return $dados;
    }

    public function consultarRegraSplit($id, $splitId)
    {
        try {
            $estabelecimento = $this->estabelecimentoService->buscarEstabelecimento($id);
            $regra = $this->splitPreService->consultarRegraSplitPre($id, $splitId);
            
            return view('estabelecimentos.regra-detalhes', compact('estabelecimento', 'regra'));
        } catch (\Exception $e) {
            Log::error('Erro ao consultar regra de split: ' . $e->getMessage());
            return redirect()->route('estabelecimentos.show', $id)
                           ->with('error', 'Erro ao consultar regra de split.');
        }
    }

    public function deletarRegraSplit($id, $splitId)
    {
        try {
            $this->splitPreService->deletarRegraSplitPre($id, $splitId);
            
            return redirect()->route('estabelecimentos.show', $id)
                           ->with('success', 'Regra de split excluída com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao deletar regra de split: ' . $e->getMessage());
            return back()->with('error', 'Erro ao excluir regra de split.');
        }
    }
} 