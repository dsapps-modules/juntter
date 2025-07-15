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
        if (Auth::check()) {
            $user = Auth::user();
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
                    return redirect()->route('dashboard');
            }
        }
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
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
