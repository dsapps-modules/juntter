<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Models\LinkPagamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkPagamentoDetailController extends Controller
{
    public function __invoke(Request $request, LinkPagamento $linkPagamento): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $estabelecimentoId = $user->getEstabelecimentoId();

        if ($estabelecimentoId !== null && (string) $linkPagamento->estabelecimento_id !== (string) $estabelecimentoId) {
            abort(403, 'Acesso negado');
        }

        return response()->json([
            'link' => [
                'id' => $linkPagamento->id,
                'estabelecimento_id' => $linkPagamento->estabelecimento_id,
                'codigo_unico' => $linkPagamento->codigo_unico,
                'tipo_pagamento' => $linkPagamento->tipo_pagamento ?? 'CARTAO',
                'descricao' => $linkPagamento->descricao,
                'valor' => (string) $linkPagamento->valor,
                'parcelas' => $linkPagamento->parcelas,
                'juros' => $linkPagamento->juros,
                'status' => $linkPagamento->status,
                'data_expiracao' => $linkPagamento->data_expiracao?->format('Y-m-d'),
                'data_vencimento' => $linkPagamento->data_vencimento?->format('Y-m-d'),
                'data_limite_pagamento' => $linkPagamento->data_limite_pagamento?->format('Y-m-d'),
                'url_retorno' => $linkPagamento->url_retorno,
                'url_webhook' => $linkPagamento->url_webhook,
                'dados_cliente_preenchidos' => $linkPagamento->dados_cliente['preenchidos'] ?? [],
                'instrucoes_boleto' => $linkPagamento->instrucoes_boleto ?? [],
            ],
        ]);
    }
}
