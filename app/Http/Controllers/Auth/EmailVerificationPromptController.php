<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();
        
        if ($user->hasVerifiedEmail()) {
            return $this->redirectBasedOnUserLevel($user);
        }
        
        return view('auth.verify-email');
    }
    
    /**
     * Redireciona baseado no nível de acesso do usuário
     */
    private function redirectBasedOnUserLevel($user): RedirectResponse
    {
        switch ($user->nivel_acesso) {
            case 'super_admin':
                return redirect()->intended(route('super_admin.dashboard'));
            case 'admin':
                return redirect()->intended(route('admin.dashboard'));
            case 'vendedor':
                return redirect()->intended(route('vendedor.dashboard'));
            default:
                return redirect()->intended(RouteServiceProvider::HOME);
        }
    }
}
