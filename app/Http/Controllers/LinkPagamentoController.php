<?php

namespace App\Http\Controllers;

use App\Models\LinkPagamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class LinkPagamentoController extends Controller
{
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
     * Listar todos os links de pagamento do estabelecimento
     */
    public function index()
    {
        $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
        
        if (!$estabelecimentoId) {
            return redirect()->route('dashboard')->with('error', 'Estabelecimento não encontrado');
        }

        $links = LinkPagamento::where('estabelecimento_id', $estabelecimentoId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('links-pagamento.index', compact('links'));
    }

    /**
     * Mostrar formulário para criar novo link
     */
    public function create()
    {
        return view('links-pagamento.create');
    }

    /**
     * Salvar novo link de pagamento
     */
    public function store(Request $request)
    {
        try {   

            $valor = $this->converterValorParaCentavos($request->input('valor')) / 100;
            $parcelas = (int) $request->input('parcelas');
            
           
            // Validar parcelas antes da validação
            if($parcelas > 1) {
                $valorMinimoParcela = 5.00;
                $valorParcela = $valor / $parcelas;
                
              

                if($valorParcela < $valorMinimoParcela) {
                  
                    return redirect()->back()->with('error', 'O valor mínimo de cada parcela é de R$ 5,00');
                }
            }

         

            $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
            
            if (!$estabelecimentoId) {
                return redirect()->back()->with('error', 'Estabelecimento não encontrado');
            }

            $dados = $request->validate([
                'descricao' => 'nullable|string|max:1000',
                'valor' => 'required|string',
                'parcelas' => 'nullable|integer|min:1|max:18',
                'juros' => 'required|in:CLIENT,ESTABLISHMENT',
                'data_expiracao' => 'nullable|date|after:today',
                'url_retorno' => 'nullable|url',
                'url_webhook' => 'nullable|url',
                'dados_cliente_preenchidos' => 'nullable|array',
                'dados_cliente_preenchidos.nome' => 'nullable|string|max:255',
                'dados_cliente_preenchidos.sobrenome' => 'nullable|string|max:255',

                'dados_cliente_preenchidos.email' => 'nullable|email|max:255',
                'dados_cliente_preenchidos.telefone' => 'nullable|string|max:20',
                'dados_cliente_preenchidos.documento' => 'nullable|string|max:20',
                'dados_cliente_preenchidos.endereco' => 'nullable|array',
                'dados_cliente_preenchidos.endereco.rua' => 'nullable|string|max:255',
                'dados_cliente_preenchidos.endereco.numero' => 'nullable|string|max:20',
                'dados_cliente_preenchidos.endereco.bairro' => 'nullable|string|max:255',
                'dados_cliente_preenchidos.endereco.cidade' => 'nullable|string|max:255',
                'dados_cliente_preenchidos.endereco.estado' => 'nullable|string|max:2',
                'dados_cliente_preenchidos.endereco.cep' => 'nullable|string|max:10',
                'dados_cliente_preenchidos.endereco.complemento' => 'nullable|string|max:255',
            ]);

            // Converter valor para centavos
            $valorCentavos = $this->converterValorParaCentavos($dados['valor']);
            $valorFloat = $valorCentavos / 100;

            // Processar parcelas
            if (empty($dados['parcelas'])) {
                $dados['parcelas'] = 1; // Apenas à vista
            }

            // Processar dados do cliente - sempre obrigatórios
            $dadosCliente = [
                'nome_obrigatorio' => true,
                'email_obrigatorio' => true,
                'telefone_obrigatorio' => true,
                'documento_obrigatorio' => true,
                'endereco_obrigatorio' => true,
                'preenchidos' => $dados['dados_cliente_preenchidos'] ?? null,
            ];

            $link = LinkPagamento::create([
                'estabelecimento_id' => $estabelecimentoId,
                'codigo_unico' => LinkPagamento::gerarCodigoUnico(),
                'descricao' => $dados['descricao'],
                'valor' => $valorFloat,
                'valor_centavos' => $valorCentavos,
                'parcelas' => $dados['parcelas'],
                'juros' => $dados['juros'],
                'data_expiracao' => $dados['data_expiracao'],
                'dados_cliente' => $dadosCliente,
                'url_retorno' => $dados['url_retorno'],
                'url_webhook' => $dados['url_webhook'],
                'status' => 'ATIVO'
            ]);

            return redirect()->route('links-pagamento.show', $link->id)
                ->with('success', 'Link de pagamento criado com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao criar link de pagamento: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro ao criar link de pagamento: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar detalhes do link
     */
    public function show(LinkPagamento $linkPagamento)
    {
        // Verificar se o usuário tem acesso a este link
        $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
        
        if ($linkPagamento->estabelecimento_id !== $estabelecimentoId) {
            abort(403, 'Acesso negado');
        }

        return view('links-pagamento.show', compact('linkPagamento'));
    }

    /**
     * Mostrar formulário para editar link
     */
    public function edit(LinkPagamento $linkPagamento)
    {
        // Verificar se o usuário tem acesso a este link
        $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
        
        if ($linkPagamento->estabelecimento_id !== $estabelecimentoId) {
            abort(403, 'Acesso negado');
        }

        return view('links-pagamento.edit', compact('linkPagamento'));
    }

    /**
     * Atualizar link de pagamento
     */
    public function update(Request $request, LinkPagamento $linkPagamento)
    {
        try {
            $valor = $this->converterValorParaCentavos($request->input('valor'))/ 100;
            $parcelas = (int) $request->input('parcelas');
            
          
            
            // Validar parcelas antes da validação
            if($parcelas > 1) {
                $valorMinimoParcela = 5.00;
                $valorParcela = $valor / $parcelas;
               

                if($valorParcela < $valorMinimoParcela) {
                  
                    return redirect()->back()->with('error', 'O valor mínimo de cada parcela é de R$ 5,00');
                }
            }

            // Verificar se o usuário tem acesso a este link
            $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
            
            if ($linkPagamento->estabelecimento_id !== $estabelecimentoId) {
                abort(403, 'Acesso negado');
            }

            $dados = $request->validate([
                'descricao' => 'nullable|string|max:1000',
                'valor' => 'required|string',
                'parcelas' => 'nullable|integer|min:1|max:18',
                'juros' => 'required|in:CLIENT,ESTABLISHMENT',
                'data_expiracao' => 'nullable|date|after:today',
                'url_retorno' => 'nullable|url',
                'url_webhook' => 'nullable|url',
                'dados_cliente_preenchidos' => 'nullable|array',
                'dados_cliente_preenchidos.nome' => 'nullable|string|max:255',
                'dados_cliente_preenchidos.sobrenome' => 'nullable|string|max:255',

                'dados_cliente_preenchidos.email' => 'nullable|email|max:255',
                'dados_cliente_preenchidos.telefone' => 'nullable|string|max:20',
                'dados_cliente_preenchidos.documento' => 'nullable|string|max:20',
                'dados_cliente_preenchidos.endereco' => 'nullable|array',
                'dados_cliente_preenchidos.endereco.rua' => 'nullable|string|max:255',
                'dados_cliente_preenchidos.endereco.numero' => 'nullable|string|max:20',
                'dados_cliente_preenchidos.endereco.bairro' => 'nullable|string|max:255',
                'dados_cliente_preenchidos.endereco.cidade' => 'nullable|string|max:255',
                'dados_cliente_preenchidos.endereco.estado' => 'nullable|string|max:2',
                'dados_cliente_preenchidos.endereco.cep' => 'nullable|string|max:10',
                'dados_cliente_preenchidos.endereco.complemento' => 'nullable|string|max:255',
            ]);

            // Converter valor para centavos
            $valorCentavos = $this->converterValorParaCentavos($dados['valor']);
            $valorFloat = $valorCentavos / 100;

            // Processar parcelas
            if (empty($dados['parcelas'])) {
                $dados['parcelas'] = 1; // Apenas à vista
            }

            // Processar dados do cliente - sempre obrigatórios
            $dadosCliente = [
                'nome_obrigatorio' => true,
                'email_obrigatorio' => true,
                'telefone_obrigatorio' => true,
                'documento_obrigatorio' => true,
                'endereco_obrigatorio' => true,
                'preenchidos' => $dados['dados_cliente_preenchidos'] ?? null,
            ];

            $linkPagamento->update([
                'descricao' => $dados['descricao'],
                'valor' => $valorFloat,
                'valor_centavos' => $valorCentavos,
                'parcelas' => $dados['parcelas'],
                'juros' => $dados['juros'],
                'data_expiracao' => $dados['data_expiracao'],
                'dados_cliente' => $dadosCliente,
                'url_retorno' => $dados['url_retorno'],
                'url_webhook' => $dados['url_webhook'],
            ]);

            return redirect()->route('links-pagamento.show', $linkPagamento->id)
                ->with('success', 'Link de pagamento atualizado com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar link de pagamento: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro ao atualizar link de pagamento: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Alterar status do link
     */
    public function alterarStatus(Request $request, LinkPagamento $linkPagamento)
    {
        try {
            // Verificar se o usuário tem acesso a este link
            $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
            
            if ($linkPagamento->estabelecimento_id !== $estabelecimentoId) {
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
            Log::error('Erro ao alterar status do link: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao alterar status'], 500);
        }
    }

    /**
     * Excluir link de pagamento
     */
    public function destroy(LinkPagamento $linkPagamento)
    {
        try {
            // Verificar se o usuário tem acesso a este link
            $estabelecimentoId = Auth::user()?->vendedor?->estabelecimento_id;
            
            if ($linkPagamento->estabelecimento_id !== $estabelecimentoId) {
                abort(403, 'Acesso negado');
            }

            $linkPagamento->delete();

            return redirect()->route('links-pagamento.index')
                ->with('success', 'Link de pagamento excluído com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao excluir link de pagamento: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro ao excluir link de pagamento: ' . $e->getMessage());
        }
    }
}
