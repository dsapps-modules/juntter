<?php

namespace App\Http\Controllers;

use App\Models\LinkPagamento;
use App\Services\PixService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

        $valorFloat = (float) $valor;

        // Validar valor mínimo (1 centavo = R$ 0,01)
        if ($valorFloat < 0.01) {
            throw new \Exception('O valor deve ser pelo menos R$ 0,01');
        }

        return (int) ($valorFloat * 100);
    }

    /**
     * Listar todos os links de pagamento PIX do estabelecimento
     */
    public function index()
    {
        return redirect('/app/links-pagamento');
    }

    /**
     * Mostrar formulário para criar novo link PIX
     */
    public function create()
    {
        return redirect('/app/links-pagamento/novo?tipo=PIX');
    }

    /**
     * Salvar novo link de pagamento PIX
     */
    public function store(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        try {
            $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;

            if (! $estabelecimentoId) {
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
                'status' => 'ATIVO',
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Link de pagamento PIX criado com sucesso!',
                    'redirect' => '/app/links-pagamento',
                    'link_id' => $link->id,
                ]);
            }

            return redirect()->route('links-pagamento-pix.show', $link->id)
                ->with('success', 'Link de pagamento PIX criado com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao criar link de pagamento PIX: '.$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Erro ao criar link de pagamento PIX: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erro ao criar link de pagamento PIX: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar detalhes do link PIX
     */
    public function show(LinkPagamento $linkPagamento)
    {
        return redirect('/app/links-pagamento/'.$linkPagamento->id.'/editar');
    }

    /**
     * Mostrar formulário para editar link PIX
     */
    public function edit(LinkPagamento $linkPagamento)
    {
        return redirect('/app/links-pagamento/'.$linkPagamento->id.'/editar');
    }

    /**
     * Atualizar link de pagamento PIX
     */
    public function update(Request $request, LinkPagamento $linkPagamento): JsonResponse|\Illuminate\Http\RedirectResponse
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

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Link de pagamento PIX atualizado com sucesso!',
                    'redirect' => '/app/links-pagamento',
                ]);
            }

            return redirect()->route('links-pagamento-pix.show', $linkPagamento->id)
                ->with('success', 'Link de pagamento PIX atualizado com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar link de pagamento PIX: '.$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Erro ao atualizar link de pagamento PIX: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erro ao atualizar link de pagamento PIX: '.$e->getMessage())
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

            if (! in_array($status, ['ATIVO', 'INATIVO'])) {
                return response()->json(['error' => 'Status inválido'], 400);
            }

            $linkPagamento->update(['status' => $status]);

            return response()->json([
                'success' => true,
                'message' => 'Status alterado com sucesso',
                'status' => $status,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao alterar status do link PIX: '.$e->getMessage());

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
            Log::error('Erro ao excluir link de pagamento PIX: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Erro ao excluir link de pagamento PIX: '.$e->getMessage());
        }
    }
}
