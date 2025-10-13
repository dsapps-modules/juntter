<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstabelecimentoController;
use App\Http\Controllers\CobrancaController;
use App\Http\Controllers\LinkPagamentoBoletoController;
use App\Http\Controllers\LinkPagamentoPixController;
use App\Http\Controllers\LinkPagamentoController;
use App\Http\Controllers\PagamentoClienteController;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PasswordController;

// Rota dashboard dinâmica
Route::middleware(['auth', 'verified'])->get('/dashboard', function () {
    return redirect(RouteServiceProvider::home());
})->name('dashboard');

// Página principal (pública)
Route::get('/', function () {
    return view('checkout');
})->name('checkout');

// Rotas públicas para pagamento do cliente (apenas cartão)
Route::get('/pagamento/{codigoUnico}', [  PagamentoClienteController::class, 'mostrarPagamento'])->name('pagamento.link');
Route::post('/pagamento/{codigoUnico}/cartao', [PagamentoClienteController::class, 'processarCartao'])->name('pagamento.cartao');
Route::get('/pagamento/{codigoUnico}/status', [PagamentoClienteController::class, 'verificarStatus'])->name('pagamento.status');

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


    Route::middleware(['nivel.acesso:vendedor', 'must.change.password'])->group(function () {
    Route::get('/vendedor/dashboard', [DashboardController::class, 'vendedorDashboard'])->name('vendedor.dashboard');
    Route::get('/cobranca', [CobrancaController::class, 'index'])->name('cobranca.index');
    Route::post('/cobranca/credito-vista', [CobrancaController::class, 'criarCreditoVista'])->name('cobranca.credito-vista.store');
    Route::get('/cobranca/planos', [CobrancaController::class, 'listarPlanos'])->name('cobranca.planos');
    Route::get('/cobranca/planos/{id}', [CobrancaController::class, 'detalhesPlano'])->name('cobranca.plano.detalhes');
    Route::get('/cobranca/saldoextrato', [CobrancaController::class, 'saldoExtrato'])->name('cobranca.saldoextrato');

    // Route::get('/cobranca/pix', function () {
    //     return view('cobranca.pix');
    // })->name('cobranca.pix');

    // Route::get('/cobranca/pagarcontas', function () {
    //     return view('cobranca.pagarcontas');
    // })->name('cobranca.pagarcontas');

    // Route::get('/cobranca/recorrente', function () {
    //     return view('cobranca.recorrente');
    // })->name('cobranca.recorrente');

    
    // Rotas de API para transações
    Route::post('/cobranca/transacao/credito', [CobrancaController::class, 'criarTransacaoCredito'])->name('cobranca.transacao.credito');
    Route::post('/cobranca/transacao/pix', [CobrancaController::class, 'criarTransacaoPix'])->name('cobranca.transacao.pix');
    Route::post('/cobranca/boleto', [CobrancaController::class, 'criarBoleto'])->name('cobranca.boleto.criar');
    Route::get('/cobranca/simular', [CobrancaController::class, 'mostrarSimulacao'])->name('cobranca.simular');
    Route::post('/cobranca/simular', [CobrancaController::class, 'simularTransacao'])->name('cobranca.transacao.simular');
    Route::get('/cobranca/transacao/{id}', [CobrancaController::class, 'detalhesTransacao'])->name('cobranca.transacao.detalhes');
    Route::get('/cobranca/boleto/{id}', [CobrancaController::class, 'detalhesBoleto'])->name('cobranca.boleto.detalhes');
    Route::get('/cobranca/transacao/{id}/qrcode', [CobrancaController::class, 'obterQrCodePix'])->name('cobranca.transacao.qrcode');
    Route::post('/cobranca/transacao/{id}/estornar', [CobrancaController::class, 'estornarTransacao'])->name('cobranca.transacao.estornar');
    Route::post('/cobranca/transacao/{id}/antifraud-auth', [CobrancaController::class, 'autenticarAntifraude'])->name('cobranca.transacao.antifraud-auth');

    // Rotas para Links de Pagamento - Cartão
        Route::get('/links-pagamento', [LinkPagamentoController::class, 'index'])->name('links-pagamento.index');
    Route::get('/links-pagamento/create', [LinkPagamentoController::class, 'create'])->name('links-pagamento.create');
    Route::post('/links-pagamento', [LinkPagamentoController::class, 'store'])->name('links-pagamento.store');
    Route::get('/links-pagamento/{linkPagamento}', [LinkPagamentoController::class, 'show'])->name('links-pagamento.show');
    Route::get('/links-pagamento/{linkPagamento}/edit', [LinkPagamentoController::class, 'edit'])->name('links-pagamento.edit');
    Route::put('/links-pagamento/{linkPagamento}', [LinkPagamentoController::class, 'update'])->name('links-pagamento.update');
    Route::delete('/links-pagamento/{linkPagamento}', [LinkPagamentoController::class, 'destroy'])->name('links-pagamento.destroy');
    Route::patch('/links-pagamento/{linkPagamento}/status', [LinkPagamentoController::class, 'alterarStatus'])->name('links-pagamento.status');

    // Rotas para Links de Pagamento - PIX
    Route::get('/links-pagamento-pix', [LinkPagamentoPixController::class, 'index'])->name('links-pagamento-pix.index');
    Route::get('/links-pagamento-pix/create', [LinkPagamentoPixController::class, 'create'])->name('links-pagamento-pix.create');
    Route::post('/links-pagamento-pix', [LinkPagamentoPixController::class, 'store'])->name('links-pagamento-pix.store');
    Route::get('/links-pagamento-pix/{linkPagamento}', [LinkPagamentoPixController::class, 'show'])->name('links-pagamento-pix.show');
    Route::get('/links-pagamento-pix/{linkPagamento}/edit', [LinkPagamentoPixController::class, 'edit'])->name('links-pagamento-pix.edit');
    Route::put('/links-pagamento-pix/{linkPagamento}', [LinkPagamentoPixController::class, 'update'])->name('links-pagamento-pix.update');
    Route::delete('/links-pagamento-pix/{linkPagamento}', [LinkPagamentoPixController::class, 'destroy'])->name('links-pagamento-pix.destroy');
    Route::patch('/links-pagamento-pix/{linkPagamento}/status', [LinkPagamentoPixController::class, 'alterarStatus'])->name('links-pagamento-pix.status');

    // Rotas para Links de Pagamento - Boleto
    Route::get('/links-pagamento-boleto', [LinkPagamentoBoletoController::class, 'index'])->name('links-pagamento-boleto.index');
    Route::get('/links-pagamento-boleto/create', [LinkPagamentoBoletoController::class, 'create'])->name('links-pagamento-boleto.create');
    Route::post('/links-pagamento-boleto', [LinkPagamentoBoletoController::class, 'store'])->name('links-pagamento-boleto.store');
    Route::get('/links-pagamento-boleto/{linkPagamento}', [LinkPagamentoBoletoController::class, 'show'])->name('links-pagamento-boleto.show');
    Route::get('/links-pagamento-boleto/{linkPagamento}/edit', [LinkPagamentoBoletoController::class, 'edit'])->name('links-pagamento-boleto.edit');
    Route::put('/links-pagamento-boleto/{linkPagamento}', [LinkPagamentoBoletoController::class, 'update'])->name('links-pagamento-boleto.update');
    Route::delete('/links-pagamento-boleto/{linkPagamento}', [LinkPagamentoBoletoController::class, 'destroy'])->name('links-pagamento-boleto.destroy');
    Route::patch('/links-pagamento-boleto/{linkPagamento}/status', [LinkPagamentoBoletoController::class, 'toggleStatus'])->name('links-pagamento-boleto.status');

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

// Rotas para troca de senha obrigatória 
Route::middleware('auth')->group(function () {
    Route::get('/password/change', [PasswordController::class, 'showChangeForm'])->name('password.change');
    Route::post('/password/change', [PasswordController::class, 'changePassword'])->name('password.change.post');
});

// Rotas públicas para processar pagamentos via link
Route::post('/pagamento/{codigo}/pix', [PagamentoClienteController::class, 'processarPix'])->name('pagamento.pix');
Route::post('/pagamento/{codigo}/boleto', [PagamentoClienteController::class, 'processarBoleto'])->name('pagamento.boleto');
Route::post('/pagamento/{codigo}/antifraud-auth', [PagamentoClienteController::class, 'autenticarAntifraude'])->name('pagamento.antifraud-auth');

// Rotas de autenticação do Breeze
require __DIR__.'/auth.php';