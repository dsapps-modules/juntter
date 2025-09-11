<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View|RedirectResponse
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        
        // Redireciona baseado no nível de acesso
        switch ($user->nivel_acesso) {
            case 'super_admin':
                return redirect()->intended(route('super_admin.dashboard'));
            case 'admin':
                return redirect()->intended(route('admin.dashboard'));
            case 'vendedor':
                return redirect()->intended(route('vendedor.dashboard'));
            case 'comprador':
                return redirect()->intended(route('comprador.dashboard'));
            default:
        return redirect()->intended(RouteServiceProvider::HOME);
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Logout completo
        Auth::guard('web')->logout();

        // Invalida TODA a sessão
        $request->session()->invalidate();

        // Gera nova sessão
        $request->session()->regenerateToken();

        // Limpa dados da sessão
        $request->session()->flush();

        return redirect()->route('checkout');
    }
}
