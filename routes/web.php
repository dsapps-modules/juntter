<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;



// Página principal (pública)
Route::get('/', function () {
    return view('checkout');
})->name('checkout');

// Página de acesso não autorizado
Route::get('/unauthorized', function () {
    return view('auth.unauthorized');
})->name('unauthorized');



// Rotas protegidas por nível de acesso
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/superadmin/dashboard', [DashboardController::class, 'superAdminDashboard'])
        ->middleware('nivel.acesso:super_admin')
        ->name('super_admin.dashboard');
    Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard'])
        ->middleware('nivel.acesso:admin')
        ->name('admin.dashboard');
    Route::get('/vendedor/dashboard', [DashboardController::class, 'vendedorDashboard'])
        ->middleware('nivel.acesso:vendedor')
        ->name('vendedor.dashboard');
    Route::get('/comprador/dashboard', [DashboardController::class, 'compradorDashboard'])
        ->middleware('nivel.acesso:comprador')
        ->name('comprador.dashboard');
});

// Rotas de perfil do Breeze
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rotas de autenticação do Breeze
require __DIR__.'/auth.php';
