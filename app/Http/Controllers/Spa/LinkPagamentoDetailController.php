<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Models\LinkPagamento;
use App\Services\PaytimePricingCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkPagamentoDetailController extends Controller
{
    public function __construct(
        private readonly PaytimePricingCacheService $pricingCacheService,
    ) {}

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
                'valor_centavos' => (int) $linkPagamento->valor_centavos,
                'parcelas' => $linkPagamento->parcelas,
                'parcelas_maximas' => $linkPagamento->parcelas_maximas,
                'parcelas_permitidas' => $linkPagamento->parcelas_permitidas,
                'juros' => $linkPagamento->juros,
                'status' => $linkPagamento->status,
                'data_expiracao' => $linkPagamento->data_expiracao?->format('Y-m-d'),
                'data_vencimento' => $linkPagamento->data_vencimento?->format('Y-m-d'),
                'data_limite_pagamento' => $linkPagamento->data_limite_pagamento?->format('Y-m-d'),
                'url_retorno' => $linkPagamento->url_retorno,
                'url_webhook' => $linkPagamento->url_webhook,
                'url_completa' => $linkPagamento->url_completa,
                'dados_cliente_preenchidos' => $linkPagamento->dados_cliente['preenchidos'] ?? [],
                'instrucoes_boleto' => $linkPagamento->instrucoes_boleto ?? [],
                'created_at' => $linkPagamento->created_at?->format('Y-m-d H:i:s'),
            ],
            'payment_summary' => $this->buildPaymentSummary($linkPagamento),
        ]);
    }

    /**
     * @return array<string, int|float|string|null>
     */
    private function buildPaymentSummary(LinkPagamento $linkPagamento): array
    {
        $baseAmountCents = (int) $linkPagamento->valor_centavos;
        $feeAmountCents = $this->resolvePixFeeCents($linkPagamento);
        $totalAmountCents = $baseAmountCents + max(0, $feeAmountCents);

        return [
            'base_amount_cents' => $baseAmountCents,
            'base_amount_formatted' => $this->formatMoney($baseAmountCents),
            'fee_amount_cents' => max(0, $feeAmountCents),
            'fee_amount_formatted' => $this->formatMoney(max(0, $feeAmountCents)),
            'total_amount_cents' => $totalAmountCents,
            'total_amount_formatted' => $this->formatMoney($totalAmountCents),
        ];
    }

    private function formatMoney(int $amountInCents): string
    {
        return 'R$ '.number_format($amountInCents / 100, 2, ',', '.');
    }

    private function resolvePixFeeCents(LinkPagamento $linkPagamento): int
    {
        if ($linkPagamento->juros !== 'CLIENT') {
            return 0;
        }

        $establishmentId = (string) $linkPagamento->estabelecimento_id;

        return max(0, $this->pricingCacheService->resolvePixOutFeeCents($establishmentId));
    }
}
