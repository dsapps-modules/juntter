<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Página principal (pública)
Route::get('/', function () {
    return view('checkout');
})->name('checkout');

// Rotas de autenticação
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rotas de cadastro
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

// Página de acesso não autorizado
Route::get('/unauthorized', [AuthController::class, 'unauthorized'])->name('unauthorized');

// Página de recuperação de senha
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');

// Rotas protegidas por nível de acesso
Route::middleware(['auth', 'nivel.acesso:super_admin'])->group(function () {
    Route::get('/superadmin/dashboard', [DashboardController::class, 'superAdminDashboard'])->name('super_admin.dashboard');
});

Route::middleware(['auth', 'nivel.acesso:admin'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');
});

Route::middleware(['auth', 'nivel.acesso:vendedor'])->group(function () {
    Route::get('/vendedor/dashboard', [DashboardController::class, 'vendedorDashboard'])->name('vendedor.dashboard');
});

Route::middleware(['auth', 'nivel.acesso:comprador'])->group(function () {
    Route::get('/comprador/dashboard', [DashboardController::class, 'compradorDashboard'])->name('comprador.dashboard');
});

