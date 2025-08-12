<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstabelecimentoController;
use App\Http\Controllers\CobrancaController;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

// Rota dashboard dinâmica
Route::middleware(['auth', 'verified'])->get('/dashboard', function () {
    return redirect(RouteServiceProvider::home());
})->name('dashboard');



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

            
    Route::middleware('nivel.acesso:vendedor')->group(function () {
    Route::get('/cobranca', [CobrancaController::class, 'index'])->name('cobranca.index');
    
    // Route::get('/cobranca/recorrente', function () {
//     return view('cobranca.recorrente');
// })->name('cobranca.recorrente');
    
    Route::get('/cobranca/planos', [CobrancaController::class, 'listarPlanos'])->name('cobranca.planos');
    Route::get('/cobranca/planos/{id}', [CobrancaController::class, 'detalhesPlano'])->name('cobranca.plano.detalhes');
    
    // Route::get('/cobranca/pix', function () {
    //     return view('cobranca.pix');
    // })->name('cobranca.pix');

    // Route::get('/cobranca/pagarcontas', function () {
    //     return view('cobranca.pagarcontas');
    // })->name('cobranca.pagarcontas');

    Route::get('/cobranca/saldoextrato', [CobrancaController::class, 'saldoExtrato'])->name('cobranca.saldoextrato');
    
    // Rotas de API para transações
    Route::post('/cobranca/transacao/credito', [CobrancaController::class, 'criarTransacaoCredito'])->name('cobranca.transacao.credito');
    Route::post('/cobranca/transacao/pix', [CobrancaController::class, 'criarTransacaoPix'])->name('cobranca.transacao.pix');
    Route::post('/cobranca/boleto', [CobrancaController::class, 'criarBoleto'])->name('cobranca.boleto.criar');
    Route::post('/cobranca/simular', [CobrancaController::class, 'simularTransacao'])->name('cobranca.transacao.simular');
    Route::get('/cobranca/transacao/{id}', [CobrancaController::class, 'detalhesTransacao'])->name('cobranca.transacao.detalhes');
    Route::get('/cobranca/transacao/{id}/qrcode', [CobrancaController::class, 'obterQrCodePix'])->name('cobranca.transacao.qrcode');
    Route::post('/cobranca/transacao/{id}/estornar', [CobrancaController::class, 'estornarTransacao'])->name('cobranca.transacao.estornar');

});
});

// Rotas de Estabelecimentos (apenas admin e super admin)
Route::middleware(['auth', 'verified', 'nivel.acesso:admin'])->group(function () {
    Route::get('/estabelecimentos/{id}', [EstabelecimentoController::class, 'show'])
        ->name('estabelecimentos.show');
    
    Route::get('/estabelecimentos/{id}/edit', [EstabelecimentoController::class, 'edit'])
        ->name('estabelecimentos.edit');
    
    Route::put('/estabelecimentos/{id}', [EstabelecimentoController::class, 'update'])
        ->name('estabelecimentos.update');
    
    // Rotas para gerenciamento de Split Pré
    Route::post('/estabelecimentos/{id}/split-pre', [EstabelecimentoController::class, 'criarRegraSplit'])
        ->name('estabelecimentos.split-pre.store');
    Route::get('/estabelecimentos/{id}/split-pre/{splitId}', [EstabelecimentoController::class, 'consultarRegraSplit'])
        ->name('estabelecimentos.split-pre.show');
    Route::delete('/estabelecimentos/{id}/split-pre/{splitId}', [EstabelecimentoController::class, 'deletarRegraSplit'])
        ->name('estabelecimentos.split-pre.destroy');
 
});

// Rotas de perfil do Breeze
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Rota para view de alterar senha no dashboard
    Route::get('/profile/password', function() {
        return view('profile.dashboard.password');
    })->name('profile.password');
});

// Rotas de autenticação do Breeze
require __DIR__.'/auth.php';
