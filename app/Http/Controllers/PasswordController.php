<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Mostra o formulário de troca de senha.
     */
    public function showChangeForm(): RedirectResponse
    {
        $user = Auth::user();

        if (! $user?->vendedor || ! $user->vendedor->must_change_password) {
            return redirect()->route('spa', ['any' => 'home'])
                ->with('error', 'Você não precisa trocar sua senha no momento.');
        }

        return redirect()->route('spa', ['any' => 'change-password']);
    }

    /**
     * Processa a troca de senha.
     */
    public function changePassword(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        if (! $user?->vendedor || ! $user->vendedor->must_change_password) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Você não precisa trocar sua senha no momento.',
                    'redirect' => '/app/home',
                ], 403);
            }

            return redirect()->route('spa', ['any' => 'home'])
                ->with('error', 'Você não precisa trocar sua senha no momento.');
        }

        $request->validate([
            'password' => 'required|min:8|confirmed',
        ], [
            'password.required' => 'A nova senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        if ($user->vendedor) {
            $user->vendedor->update([
                'must_change_password' => false,
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Senha alterada com sucesso!',
                'redirect' => '/app/login',
            ]);
        }

        return redirect()->route('login')
            ->with('success', 'Senha alterada com sucesso! Faça login novamente com sua nova senha.');
    }
}
