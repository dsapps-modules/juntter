<?php

use App\Http\Controllers\PaytimeWebhookController;
use App\Http\Controllers\Spa\CobrancaBoletoDestroyController;
use App\Http\Controllers\Spa\CobrancaBoletoDetailController;
use App\Http\Controllers\Spa\CobrancaBoletoOverviewController;
use App\Http\Controllers\Spa\CobrancaOverviewController;
use App\Http\Controllers\Spa\CobrancaPixOutController;
use App\Http\Controllers\Spa\CobrancaPlanoContratadoController;
use App\Http\Controllers\Spa\CobrancaSaldoExtratoController;
use App\Http\Controllers\Spa\DashboardOverviewController;
use App\Http\Controllers\Spa\EstabelecimentoDetailController;
use App\Http\Controllers\Spa\EstablishmentOverviewController;
use App\Http\Controllers\Spa\LinkPagamentoDetailController;
use App\Http\Controllers\Spa\LinksPagamentoOverviewController;
use App\Http\Controllers\Spa\ProfileOverviewController;
use App\Http\Controllers\Spa\VendedorAccessOverviewController;
use App\Http\Controllers\Spa\VendedoresOverviewController;
use App\Http\Controllers\Spa\VendedorFaturamentoOverviewController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/paytime', [PaytimeWebhookController::class, 'handle']);

Route::middleware(['web', 'auth', 'verified'])->group(function (): void {
    Route::get('/spa/dashboard', DashboardOverviewController::class);
    Route::get('/spa/cobranca', CobrancaOverviewController::class);
    Route::get('/spa/cobranca/boleto', CobrancaBoletoOverviewController::class);
    Route::get('/spa/cobranca/boleto/{boleto}', CobrancaBoletoDetailController::class);
    Route::delete('/spa/cobranca/boleto/{boleto}', CobrancaBoletoDestroyController::class);
    Route::get('/spa/cobranca/saldoextrato', CobrancaSaldoExtratoController::class);
    Route::get('/spa/cobranca/pix-out', CobrancaPixOutController::class);
    Route::post('/spa/cobranca/pix-out/assinatura-eletronica', [CobrancaPixOutController::class, 'requestElectronicSignatureCode']);
    Route::post('/spa/cobranca/pix-out/assinatura-eletronica/confirmar', [CobrancaPixOutController::class, 'confirmElectronicSignatureCode']);
    Route::post('/spa/cobranca/pix-out', [CobrancaPixOutController::class, 'store']);
    Route::post('/spa/cobranca/pix-out/{pixPayoutRequest}/confirmar', [CobrancaPixOutController::class, 'confirm']);
    Route::get('/spa/cobranca/planos/{planoId?}', CobrancaPlanoContratadoController::class);
    Route::get('/spa/estabelecimentos', EstablishmentOverviewController::class);
    Route::get('/spa/links-pagamento', LinksPagamentoOverviewController::class);
    Route::get('/spa/links-pagamento/{linkPagamento}', LinkPagamentoDetailController::class);
    Route::get('/spa/perfil', ProfileOverviewController::class);
    Route::get('/spa/vendedores', VendedoresOverviewController::class);
    Route::get('/spa/vendedores/acesso', VendedorAccessOverviewController::class);
    Route::get('/spa/vendedores/faturamento', VendedorFaturamentoOverviewController::class);
    Route::get('/spa/estabelecimentos/{estabelecimento}', EstabelecimentoDetailController::class);
});

if (app()->environment('local')) {
    require __DIR__.'/test_helper.php';
}
