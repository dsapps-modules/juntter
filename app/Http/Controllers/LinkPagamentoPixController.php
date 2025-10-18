<?php

namespace App\Http\Controllers;

use App\Models\LinkPagamento;
use App\Services\PixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class LinkPagamentoPixController extends Controller
{
    protected $pixService;

    public function __construct(PixService $pixService)
    {
        $this->pixService = $pixService;
    }
    /**
     * Converte valor brasileiro para centavos
     * Aceita formatos: 1.100,00, 1100,00, 1100.00, 1100
     */
    private function converterValorParaCentavos($valor)
    {
        // Remover símbolos de moeda e espaços
        $valor = preg_replace('/[R$\s]/', '', $valor);
        
        // Se tem vírgula, é formato brasileiro (1.100,00)
        if (strpos($valor, ',') !== false) {
            // Remover pontos (separadores de milhares) e trocar vírgula por ponto
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        }
        
        $valorFloat = (float)$valor;
        
        // Validar valor mínimo (1 centavo = R$ 0,01)
        if ($valorFloat < 0.01) {
            throw new \Exception('O valor deve ser pelo menos R$ 0,01');
        }
        
        return (int)($valorFloat * 100);
    }

    /**
     * Listar todos os links de pagamento PIX do estabelecimento
     */
    public function index()
    {
        $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
        
        if (!$estabelecimentoId) {
            return redirect()->route('dashboard')->with('error', 'Estabelecimento não encontrado');
        }

        $links = LinkPagamento::where('estabelecimento_id', $estabelecimentoId)
            ->where('tipo_pagamento', 'PIX')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('links-pagamento-pix.index', compact('links'));
    }

    /**
     * Mostrar formulário para criar novo link PIX
     */
    public function create()
    {
        return view('links-pagamento-pix.create');
    }

    /**
     * Salvar novo link de pagamento PIX
     */
    public function store(Request $request)
    {
        try {   
            $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
            
            if (!$estabelecimentoId) {
                return redirect()->back()->with('error', 'Estabelecimento não encontrado');
            }

            $dados = $request->validate([
                'descricao' => 'nullable|string|max:1000',
                'valor' => 'required|string',
                'juros' => 'required|in:CLIENT,ESTABLISHMENT',
                'data_expiracao' => 'nullable|date|after:today',
                'dados_cliente_preenchidos' => 'nullable|array',
                'dados_cliente_preenchidos.nome' => 'nullable|string|max:255',
                'dados_cliente_preenchidos.sobrenome' => 'nullable|string|max:255',
                'dados_cliente_preenchidos.email' => 'nullable|email|max:255',
                'dados_cliente_preenchidos.telefone' => 'nullable|string|max:20',
                'dados_cliente_preenchidos.documento' => 'nullable|string|max:20',
            ]);

            // Converter valor para centavos
            $valorCentavos = $this->converterValorParaCentavos($dados['valor']);
            $valorFloat = $valorCentavos / 100;

            // Processar dados do cliente - opcionais para PIX
            $dadosCliente = [
                'nome_obrigatorio' => false,
                'email_obrigatorio' => false,
                'telefone_obrigatorio' => false,
                'documento_obrigatorio' => false,
                'endereco_obrigatorio' => false,
                'preenchidos' => $dados['dados_cliente_preenchidos'] ?? null,
            ];

            $link = LinkPagamento::create([
                'estabelecimento_id' => $estabelecimentoId,
                'codigo_unico' => LinkPagamento::gerarCodigoUnico(),
                'descricao' => $dados['descricao'],
                'valor' => $valorFloat,
                'valor_centavos' => $valorCentavos,
                'parcelas' => '{"installments":1}', // PIX sempre à vista
                'juros' => $dados['juros'],
                'data_expiracao' => $dados['data_expiracao'],
                'dados_cliente' => $dadosCliente,
                'tipo_pagamento' => 'PIX',
                'status' => 'ATIVO'
            ]);

            return redirect()->route('links-pagamento-pix.show', $link->id)
                ->with('success', 'Link de pagamento PIX criado com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao criar link de pagamento PIX: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro ao criar link de pagamento PIX: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar detalhes do link PIX
     */
    public function show(LinkPagamento $linkPagamento)
    {
        // Verificar se o usuário tem acesso a este link
        $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
        
        if ($linkPagamento->estabelecimento_id !== $estabelecimentoId || $linkPagamento->tipo_pagamento !== 'PIX') {
            abort(403, 'Acesso negado');
        }

        return view('links-pagamento-pix.show', compact('linkPagamento'));
    }

    /**
     * Mostrar formulário para editar link PIX
     */
    public function edit(LinkPagamento $linkPagamento)
    {
        // Verificar se o usuário tem acesso a este link
        $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
        
        if ($linkPagamento->estabelecimento_id !== $estabelecimentoId || $linkPagamento->tipo_pagamento !== 'PIX') {
            abort(403, 'Acesso negado');
        }

        return view('links-pagamento-pix.edit', compact('linkPagamento'));
    }

    /**
     * Atualizar link de pagamento PIX
     */
    public function update(Request $request, LinkPagamento $linkPagamento)
    {
        try {
            // Verificar se o usuário tem acesso a este link
            $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
            
            if ($linkPagamento->estabelecimento_id !== $estabelecimentoId || $linkPagamento->tipo_pagamento !== 'PIX') {
                abort(403, 'Acesso negado');
            }

            $dados = $request->validate([
                'descricao' => 'nullable|string|max:1000',
                'valor' => 'required|string',
                'juros' => 'required|in:CLIENT,ESTABLISHMENT',
                'data_expiracao' => 'nullable|date|after:today',
                'dados_cliente_preenchidos' => 'nullable|array',
                'dados_cliente_preenchidos.nome' => 'nullable|string|max:255',
                'dados_cliente_preenchidos.sobrenome' => 'nullable|string|max:255',
                'dados_cliente_preenchidos.email' => 'nullable|email|max:255',
                'dados_cliente_preenchidos.telefone' => 'nullable|string|max:20',
                'dados_cliente_preenchidos.documento' => 'nullable|string|max:20',
            ]);

            // Converter valor para centavos
            $valorCentavos = $this->converterValorParaCentavos($dados['valor']);
            $valorFloat = $valorCentavos / 100;

            // Processar dados do cliente - opcionais para PIX
            $dadosCliente = [
                'nome_obrigatorio' => false,
                'email_obrigatorio' => false,
                'telefone_obrigatorio' => false,
                'documento_obrigatorio' => false,
                'endereco_obrigatorio' => false,
                'preenchidos' => $dados['dados_cliente_preenchidos'] ?? null,
            ];

            $linkPagamento->update([
                'descricao' => $dados['descricao'],
                'valor' => $valorFloat,
                'valor_centavos' => $valorCentavos,
                'juros' => $dados['juros'],
                'data_expiracao' => $dados['data_expiracao'],
                'dados_cliente' => $dadosCliente,
            ]);

            return redirect()->route('links-pagamento-pix.show', $linkPagamento->id)
                ->with('success', 'Link de pagamento PIX atualizado com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar link de pagamento PIX: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro ao atualizar link de pagamento PIX: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Alterar status do link PIX
     */
    public function alterarStatus(Request $request, LinkPagamento $linkPagamento)
    {
        try {
            // Verificar se o usuário tem acesso a este link
            $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
            
            if ($linkPagamento->estabelecimento_id !== $estabelecimentoId || $linkPagamento->tipo_pagamento !== 'PIX') {
                abort(403, 'Acesso negado');
            }

            $status = $request->input('status');
            
            if (!in_array($status, ['ATIVO', 'INATIVO'])) {
                return response()->json(['error' => 'Status inválido'], 400);
            }

            $linkPagamento->update(['status' => $status]);

            return response()->json([
                'success' => true,
                'message' => 'Status alterado com sucesso',
                'status' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao alterar status do link PIX: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao alterar status'], 500);
        }
    }

    /**
     * Excluir link de pagamento PIX
     */
    public function destroy(LinkPagamento $linkPagamento)
    {
        try {
            // Verificar se o usuário tem acesso a este link
            $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
            
            if ($linkPagamento->estabelecimento_id !== $estabelecimentoId || $linkPagamento->tipo_pagamento !== 'PIX') {
                abort(403, 'Acesso negado');
            }

            $linkPagamento->delete();

            return redirect()->route('links-pagamento-pix.index')
                ->with('success', 'Link de pagamento PIX excluído com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao excluir link de pagamento PIX: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro ao excluir link de pagamento PIX: ' . $e->getMessage());
        }
    }
}