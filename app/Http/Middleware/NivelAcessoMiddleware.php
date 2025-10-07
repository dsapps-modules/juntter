<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class NivelAcessoMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$niveis): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        /** @var User $user */
        $user = auth()->user();
        
        // Se nenhum nível foi especificado, permite acesso
        if (empty($niveis)) {
            return $next($request);
        }

        // Verifica se o usuário tem pelo menos um dos níveis permitidos
        foreach ($niveis as $nivel) {
            switch ($nivel) {
                case 'super_admin':
                    if ($user->isSuperAdmin()) {
                        return $next($request);
                    }
                    break;
                case 'admin':
                    if ($user->isSuperAdminOrAdmin()) {
                        return $next($request);
                    }
                    break;
                case 'vendedor':
                    if ($user->isVendedor()) {
                        return $next($request);
                    }
                    break;
            }
        }

        // Se chegou aqui, não tem permissão
        return redirect()->route('unauthorized')->with('error', 'Acesso negado. Nível de acesso insuficiente.');
    }
} 