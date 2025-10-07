<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        if ($user->hasVerifiedEmail()) {
            return $this->redirectBasedOnUserLevel($user);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->redirectBasedOnUserLevel($user);
    }
    
    /**
     * Redireciona baseado no nível de acesso do usuário
     */
    private function redirectBasedOnUserLevel($user): RedirectResponse
    {
        switch ($user->nivel_acesso) {
            case 'super_admin':
                return redirect()->intended(route('super_admin.dashboard').'?verified=1');
            case 'admin':
                return redirect()->intended(route('admin.dashboard').'?verified=1');
            case 'vendedor':
                return redirect()->intended(route('vendedor.dashboard').'?verified=1');
            default:
                return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
        }
    }
}
