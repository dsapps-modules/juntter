<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Mostra o formulário de login
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Processa o login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();
            
            // Redireciona baseado no nível de acesso
            switch ($user->nivel_acesso) {
                case 'super_admin':
                    return redirect()->route('super_admin.dashboard');
                case 'admin':
                    return redirect()->route('admin.dashboard');
                case 'vendedor':
                    return redirect()->route('vendedor.dashboard');
                case 'comprador':
                    return redirect()->route('comprador.dashboard');
                default:
                    return redirect()->route('checkout');
            }
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ])->onlyInput('email');
    }

    /**
     * Processa o logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Mostra página de acesso não autorizado
     */
    public function unauthorized()
    {
        return view('auth.unauthorized');
    }

    /**
     * Exibe o formulário de cadastro
     */
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    /**
     * Processa o cadastro de novo usuário
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required' => 'O nome é obrigatório.',
            'name.string' => 'O nome deve ser um texto.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Digite um e-mail válido.',
            'email.max' => 'O e-mail não pode ter mais de 255 caracteres.',
            'email.unique' => 'Este e-mail já está sendo usado.',
            'password.required' => 'A senha é obrigatória.',
            'password.string' => 'A senha deve ser um texto.',
            'password.min' => 'A senha deve ter pelo menos 6 caracteres.',
            'password.confirmed' => 'As senhas não coincidem. Por favor, verifique se digitou a mesma senha nos dois campos.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'nivel_acesso' => 'comprador', // padrão
        ]);

        Auth::login($user);

        return redirect()->route('comprador.dashboard');
    }
}
