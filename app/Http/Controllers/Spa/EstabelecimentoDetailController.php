<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Models\PaytimeEstablishment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstabelecimentoDetailController extends Controller
{
    public function __invoke(Request $request, PaytimeEstablishment $estabelecimento): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $user->isSuperAdminOrAdmin()) {
            abort(403, 'Acesso negado');
        }

        return response()->json([
            'establishment' => [
                'id' => $estabelecimento->id,
                'access_type' => $estabelecimento->type ?? 'ACQUIRER',
                'first_name' => $estabelecimento->first_name,
                'last_name' => $estabelecimento->last_name,
                'email' => $estabelecimento->email,
                'phone_number' => $estabelecimento->phone_number,
                'revenue' => $estabelecimento->revenue,
                'format' => $estabelecimento->type === 'COMPANY' ? 'LTDA' : 'MEI',
                'gmv' => null,
                'birthdate' => null,
                'document' => $estabelecimento->document,
                'status' => $estabelecimento->status,
                'risk' => $estabelecimento->risk,
                'active' => $estabelecimento->active,
                'city' => $estabelecimento->address_json['city'] ?? null,
            ],
        ]);
    }
}
