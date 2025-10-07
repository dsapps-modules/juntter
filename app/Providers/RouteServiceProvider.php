<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Get the dashboard route based on user role
     */
    public static function home(): string
    {
        if (!auth()->check()) {
            return '/login';
        }

        $user = auth()->user();
        
        // Redireciona baseado no nível de acesso (mesma lógica do RedirectIfAuthenticated)
        switch ($user->nivel_acesso) {
            case 'super_admin':
                return '/superadmin/dashboard';
            case 'admin':
                return '/admin/dashboard';
            case 'vendedor':
                return '/vendedor/dashboard';
            default:
                return '/dashboard';
        }
    }

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
