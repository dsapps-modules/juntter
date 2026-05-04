<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Services\BoletoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CobrancaBoletoDestroyController extends Controller
{
    public function __construct(
        private readonly BoletoService $boletoService,
    ) {}

    public function __invoke(Request $request, string $boleto): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $rawBoleto = $this->boletoService->consultarBoleto($boleto);
        $boletoData = $this->extractBoleto($rawBoleto);

        if ($this->resolveBoletoId($boletoData) === '') {
            return response()->json(['message' => 'Boleto não encontrado.'], 404);
        }

        $establishmentId = $user->getEstabelecimentoId();

        if ($establishmentId !== null && ! $this->matchesEstablishment($boletoData, $establishmentId)) {
            abort(403, 'Acesso negado');
        }

        $response = $this->boletoService->deletarBoleto($boleto);

        if (isset($response['error']) || isset($response['errors'])) {
            return response()->json([
                'message' => $response['message'] ?? 'Não foi possível cancelar o boleto.',
                'gateway_response' => $response,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Boleto cancelado com sucesso.',
            'boleto' => $response,
        ]);
    }

    private function extractBoleto(array $rawBoleto): array
    {
        $nested = data_get($rawBoleto, 'data');

        if (is_array($nested)) {
            return $nested;
        }

        return $rawBoleto;
    }

    private function resolveBoletoId(array $boleto): string
    {
        $id = $boleto['_id']
            ?? $boleto['id']
            ?? $boleto['external_id']
            ?? data_get($boleto, 'boleto._id')
            ?? data_get($boleto, 'boleto.id')
            ?? data_get($boleto, 'boleto.external_id');

        return is_scalar($id) ? (string) $id : '';
    }

    private function matchesEstablishment(array $boleto, string|int|null $establishmentId): bool
    {
        if ($establishmentId === null || (string) $establishmentId === '') {
            return true;
        }

        $boletoEstablishmentId = data_get($boleto, 'establishment.id')
            ?? data_get($boleto, 'establishment_id')
            ?? data_get($boleto, 'extra_headers.establishment_id');

        return (string) $boletoEstablishmentId === (string) $establishmentId;
    }
}
