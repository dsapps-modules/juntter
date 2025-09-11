<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MustChangePassword
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $vendedor = auth()->user()->vendedor;
        
        // Se precisa trocar senha, redireciona
        if ($vendedor && $vendedor->must_change_password) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
