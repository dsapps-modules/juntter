<?php

namespace App\Http\Controllers;

use App\Services\EstabelecimentoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EstabelecimentoController extends Controller
{
    protected $estabelecimentoService;

    public function __construct(EstabelecimentoService $estabelecimentoService)
    {
        $this->estabelecimentoService = $estabelecimentoService;
    }

    public function show($id)
    {
        try {
            $estabelecimento = $this->estabelecimentoService->buscarEstabelecimento($id);
            return view('estabelecimentos.show', compact('estabelecimento'));
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
} 