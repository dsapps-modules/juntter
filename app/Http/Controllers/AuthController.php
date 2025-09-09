<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Redirecionar para login com logout forçado (segurança)
     * 
     * Esta função garante que se alguém estiver logado e clicar em "Login"
     * na página pública, a sessão anterior será invalidada para evitar
     * que outra pessoa use a sessão de quem esqueceu o computador logado.
     */
    public function loginRedirect()
    {
        if (Auth::check()) {
            // Usuário está logado - fazer logout forçado por segurança
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
        
        // Redirecionar para login (com ou sem logout)
        return redirect()->route('login');
    }
}