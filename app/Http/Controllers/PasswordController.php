<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class PasswordController extends Controller
{
    /**
     * Mostra o formulário de troca de senha
     */
    public function showChangeForm()
    {
        $user = Auth::user();
        
        // Verifica se é vendedor e precisa trocar senha
        if (!$user->vendedor || !$user->vendedor->must_change_password) {
            return redirect()->route('vendedor.dashboard')
                ->with('error', 'Você não precisa trocar sua senha no momento.');
        }
        
        return view('auth.change-password');
    }

    /**
     * Processa a troca de senha
     */
    public function changePassword(Request $request)
    {
        $user = Auth::user();
        
        // Verifica se é vendedor e precisa trocar senha
        if (!$user->vendedor || !$user->vendedor->must_change_password) {
            return redirect()->route('vendedor.dashboard')
                ->with('error', 'Você não precisa trocar sua senha no momento.');
        }
        
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ], [
            'password.required' => 'A nova senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);
        
        // Atualiza a senha do usuário
        $user->update([
            'password' => Hash::make($request->password),
        ]);
        
        // Marca que o vendedor já trocou a senha
        if ($user->vendedor) {
            $user->vendedor->update([
                'must_change_password' => false,
            ]);
        }

        // Faz logout para quebrar a sessão
        Auth::logout();
        
        // Invalida a sessão
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Senha alterada com sucesso! Faça login novamente com sua nova senha.');
    }
}