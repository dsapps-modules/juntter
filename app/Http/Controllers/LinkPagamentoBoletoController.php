<?php

namespace App\Http\Controllers;

use App\Http\Requests\LinkBoletoRequest;
use App\Models\LinkPagamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class LinkPagamentoBoletoController extends Controller
{
    /**
     * Converte valor brasileiro para centavos
     * Aceita formatos: 1.100,00, 1100,00, 1100.00, 1100
     */
    private function converterValorParaCentavos($valor)
    {
        // Verificar se o valor está vazio
        if (empty($valor) || trim($valor) === '') {
            throw new \Exception('O valor é obrigatório');
        }

        // Remover símbolos de moeda e espaços
        $valor = preg_replace('/[R$\s]/', '', $valor);
        
        // Verificar se ainda está vazio após limpeza
        if (empty($valor)) {
            throw new \Exception('O valor é obrigatório');
        }
        
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
     * Listar todos os links de pagamento Boleto do estabelecimento
     */
    public function index()
    {
        $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
        
        if (!$estabelecimentoId) {
            return redirect()->route('dashboard')->with('error', 'Estabelecimento não encontrado');
        }

        $links = LinkPagamento::where('estabelecimento_id', $estabelecimentoId)
            ->where('tipo_pagamento', 'BOLETO')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('links-pagamento-boleto.index', compact('links'));
    }

    /**
     * Mostrar formulário de criação
     */
    public function create()
    {
        return view('links-pagamento-boleto.create');
    }

    /**
     * Salvar novo link de pagamento Boleto
     */
    public function store(LinkBoletoRequest $request)
    {
        try {   
            $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
            
            if (!$estabelecimentoId) {
                return redirect()->back()->with('error', 'Estabelecimento não encontrado');
            }

            $dados = $request->validated();

            // Converter valor para centavos
            $valorCentavos = $this->converterValorParaCentavos($dados['valor']);
            $valorFloat = $valorCentavos / 100;

            // Processar dados do cliente - obrigatórios para Boleto
            $dadosCliente = [
                'nome_obrigatorio' => true,
                'email_obrigatorio' => true,
                'telefone_obrigatorio' => true,
                'documento_obrigatorio' => true,
                'endereco_obrigatorio' => true,
                'preenchidos' => $dados['dados_cliente_preenchidos'],
            ];

            // Processar instruções do boleto
            $instrucoesBoleto = [
                'description' => $dados['instrucoes_boleto']['description'] ?? null,
                'late_fee' => [
                    'amount' => $dados['instrucoes_boleto']['late_fee']['amount']
                ],
                'interest' => [
                    'amount' => $dados['instrucoes_boleto']['interest']['amount']
                ],
                'discount' => [
                    'amount' => $dados['instrucoes_boleto']['discount']['amount'],
                    'limit_date' => $dados['instrucoes_boleto']['discount']['limit_date']
                ]
            ];

            $link = LinkPagamento::create([
                'estabelecimento_id' => $estabelecimentoId,
                'codigo_unico' => LinkPagamento::gerarCodigoUnico(),
                'descricao' => $dados['descricao'],
                'valor' => $valorFloat,
                'valor_centavos' => $valorCentavos,
                'parcelas' => json_encode(['installments' => 1 ]), // Boleto sempre à vista
                'juros' => $dados['juros'],
                'data_expiracao' => $dados['data_expiracao'],
                'data_vencimento' => $dados['data_vencimento'],
                'data_limite_pagamento' => $dados['data_limite_pagamento'],
                'dados_cliente' => $dadosCliente,
                'instrucoes_boleto' => $instrucoesBoleto,
                'tipo_pagamento' => 'BOLETO',
                'status' => 'ATIVO'
            ]);

            return redirect()->route('links-pagamento-boleto.show', $link->id)
                ->with('success', 'Link de pagamento Boleto criado com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao criar link de pagamento Boleto: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro ao criar link de pagamento Boleto: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar detalhes do link
     */
    public function show($id)
    {
        $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
        
        if (!$estabelecimentoId) {
            return redirect()->route('dashboard')->with('error', 'Estabelecimento não encontrado');
        }

        $linkPagamento = LinkPagamento::where('id', $id)
            ->where('estabelecimento_id', $estabelecimentoId)
            ->where('tipo_pagamento', 'BOLETO')
            ->firstOrFail();

        return view('links-pagamento-boleto.show', compact('linkPagamento'));
    }

    /**
     * Mostrar formulário de edição
     */
    public function edit($id)
    {
        $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
        
        if (!$estabelecimentoId) {
            return redirect()->route('dashboard')->with('error', 'Estabelecimento não encontrado');
        }

        $linkPagamento = LinkPagamento::where('id', $id)
            ->where('estabelecimento_id', $estabelecimentoId)
            ->where('tipo_pagamento', 'BOLETO')
            ->firstOrFail();

        return view('links-pagamento-boleto.edit', compact('linkPagamento'));
    }

    /**
     * Atualizar link de pagamento Boleto
     */
    public function update(Request $request, $id)
    {
        try {
            $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
            
            if (!$estabelecimentoId) {
                return redirect()->back()->with('error', 'Estabelecimento não encontrado');
            }

            $linkPagamento = LinkPagamento::where('id', $id)
                ->where('estabelecimento_id', $estabelecimentoId)
                ->where('tipo_pagamento', 'BOLETO')
                ->firstOrFail();

            $dados = $request->validate([
                'descricao' => 'nullable|string|max:1000',
                'valor' => 'required|string|min:1',
                'data_vencimento' => 'required|date|after:today',
                'data_limite_pagamento' => 'nullable|date|after:data_vencimento',
                'juros' => 'required|in:CLIENT,ESTABLISHMENT',
                'data_expiracao' => 'nullable|date|after:today',
                'dados_cliente_preenchidos' => 'required|array',
                'dados_cliente_preenchidos.nome' => 'required|string|max:255',
                'dados_cliente_preenchidos.sobrenome' => 'required|string|max:255',
                'dados_cliente_preenchidos.email' => 'required|email|max:255',
                'dados_cliente_preenchidos.telefone' => 'required|string|max:20',
                'dados_cliente_preenchidos.documento' => 'required|string|max:20',
                'dados_cliente_preenchidos.endereco' => 'required|array',
                'dados_cliente_preenchidos.endereco.rua' => 'required|string|max:255',
                'dados_cliente_preenchidos.endereco.numero' => 'required|string|max:20',
                'dados_cliente_preenchidos.endereco.bairro' => 'required|string|max:255',
                'dados_cliente_preenchidos.endereco.cidade' => 'required|string|max:255',
                'dados_cliente_preenchidos.endereco.estado' => 'required|string|max:2',
                'dados_cliente_preenchidos.endereco.cep' => 'required|string|max:10',
                'dados_cliente_preenchidos.endereco.complemento' => 'nullable|string|max:255',
                // Instruções do boleto
                'instrucoes_boleto' => 'required|array',
                'instrucoes_boleto.description' => 'nullable|string|max:500',
                'instrucoes_boleto.late_fee' => 'required|array',
                'instrucoes_boleto.late_fee.amount' => 'required|numeric|min:0',
                'instrucoes_boleto.interest' => 'required|array',
                'instrucoes_boleto.interest.amount' => 'required|numeric|min:0',
                'instrucoes_boleto.discount' => 'required|array',
                'instrucoes_boleto.discount.amount' => 'required|numeric|min:0',
                'instrucoes_boleto.discount.limit_date' => 'required|date|before:data_vencimento',
            ]);

            // Converter valor para centavos
            $valorCentavos = $this->converterValorParaCentavos($dados['valor']);
            $valorFloat = $valorCentavos / 100;

            // Processar dados do cliente - obrigatórios para Boleto
            $dadosCliente = [
                'nome_obrigatorio' => true,
                'email_obrigatorio' => true,
                'telefone_obrigatorio' => true,
                'documento_obrigatorio' => true,
                'endereco_obrigatorio' => true,
                'preenchidos' => $dados['dados_cliente_preenchidos'],
            ];

            // Processar instruções do boleto
            $instrucoesBoleto = [
                'description' => $dados['instrucoes_boleto']['description'] ?? null,
                'late_fee' => [
                    'amount' => $dados['instrucoes_boleto']['late_fee']['amount']
                ],
                'interest' => [
                    'amount' => $dados['instrucoes_boleto']['interest']['amount']
                ],
                'discount' => [
                    'amount' => $dados['instrucoes_boleto']['discount']['amount'],
                    'limit_date' => $dados['instrucoes_boleto']['discount']['limit_date']
                ]
            ];

            $linkPagamento->update([
                'descricao' => $dados['descricao'],
                'valor' => $valorFloat,
                'valor_centavos' => $valorCentavos,
                'juros' => $dados['juros'],
                'data_expiracao' => $dados['data_expiracao'],
                'data_vencimento' => $dados['data_vencimento'],
                'data_limite_pagamento' => $dados['data_limite_pagamento'],
                'dados_cliente' => $dadosCliente,
                'instrucoes_boleto' => $instrucoesBoleto,
            ]);

            return redirect()->route('links-pagamento-boleto.show', $linkPagamento->id)
                ->with('success', 'Link de pagamento Boleto atualizado com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar link de pagamento Boleto: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro ao atualizar link de pagamento Boleto: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Excluir link de pagamento Boleto
     */
    public function destroy($id)
    {
        try {
            $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
            
            if (!$estabelecimentoId) {
                return redirect()->back()->with('error', 'Estabelecimento não encontrado');
            }

            $linkPagamento = LinkPagamento::where('id', $id)
                ->where('estabelecimento_id', $estabelecimentoId)
                ->where('tipo_pagamento', 'BOLETO')
                ->firstOrFail();

            $linkPagamento->delete();

            return redirect()->route('links-pagamento-boleto.index')
                ->with('success', 'Link de pagamento Boleto excluído com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao excluir link de pagamento Boleto: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro ao excluir link de pagamento Boleto: ' . $e->getMessage());
        }
    }

    /**
     * Alternar status do link
     */
    public function toggleStatus($id)
    {
        try {
            $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
            
            if (!$estabelecimentoId) {
                return redirect()->back()->with('error', 'Estabelecimento não encontrado');
            }

            $linkPagamento = LinkPagamento::where('id', $id)
                ->where('estabelecimento_id', $estabelecimentoId)
                ->where('tipo_pagamento', 'BOLETO')
                ->firstOrFail();

            $linkPagamento->status = $linkPagamento->status === 'ATIVO' ? 'INATIVO' : 'ATIVO';
            $linkPagamento->save();

            $statusText = $linkPagamento->status === 'ATIVO' ? 'ativado' : 'desativado';
            
            return redirect()->back()
                ->with('success', "Link de pagamento Boleto {$statusText} com sucesso!");

        } catch (\Exception $e) {
            Log::error('Erro ao alterar status do link de pagamento Boleto: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro ao alterar status do link: ' . $e->getMessage());
        }
    }
}