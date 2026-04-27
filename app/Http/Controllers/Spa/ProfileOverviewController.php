<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileOverviewController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('vendedor');

        return response()->json([
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url ?? null,
                'nivel_acesso' => $user->nivel_acesso,
                'nivel_label' => $this->roleLabel($user->nivel_acesso),
                'verified' => $user->hasVerifiedEmail(),
                'email_verified_at' => $user->email_verified_at?->format('d/m/Y H:i'),
                'created_at' => $user->created_at?->format('d/m/Y'),
                'must_change_password' => (bool) ($user->vendedor?->must_change_password ?? false),
                'vendedor' => [
                    'status' => $user->vendedor?->status,
                    'sub_nivel' => $user->vendedor?->sub_nivel,
                    'estabelecimento_id' => $user->vendedor?->estabelecimento_id,
                ],
            ],
            'verification' => [
                'required' => ! $user->hasVerifiedEmail(),
                'message' => $user->hasVerifiedEmail()
                    ? 'E-mail verificado.'
                    : 'Seu e-mail ainda precisa ser verificado.',
            ],
        ]);
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'super_admin' => 'Super admin',
            'admin' => 'Admin',
            'vendedor' => 'Vendedor',
            default => 'Usuario',
        };
    }
}
