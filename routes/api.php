<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaytimeWebhookController;

// new-establishment	Novo estabelecimento cadastrado
Route::post('/webhook/paytime/create-establishment', [PaytimeWebhookController::class, 'createEstablishment']);

// updated-establishment-status	Atualização do status de um estabelecimento
Route::post('/webhook/paytime/update-establishment-status', [PaytimeWebhookController::class, 'updateEstablishmentStatus']);

// updated-establishment-data	Atualização de dados de um estabelecimento
Route::post('/webhook/paytime/update-establishment-data', [PaytimeWebhookController::class, 'updateEstablishmentData']);

// new-sub-transaction	Nova transação Sub
Route::post('/webhook/paytime/new-sub-transaction', [PaytimeWebhookController::class, 'newSubTransaction']);

// updated-sub-transaction	Transação Sub atualizada
Route::post('/webhook/paytime/updated-sub-transaction', [PaytimeWebhookController::class, 'updatedSubTransaction']);

// new-billet	Novo boleto criado
Route::post('/webhook/paytime/new-billet', [PaytimeWebhookController::class, 'newBillet']);

// updated-billet-status	Atualização do status de um boleto
Route::post('/webhook/paytime/update-billet-status', [PaytimeWebhookController::class, 'updateBilletStatus']);

// new-sub-split	Split de Transação Sub
Route::post('/webhook/paytime/new-sub-split', [PaytimeWebhookController::class, 'newSubSplit']);

// canceled-sub-split	Cancelamento de Split Sub
Route::post('/webhook/paytime/canceled-sub-split', [PaytimeWebhookController::class, 'canceledSubSplit']);


if (app()->environment('local')) {
    // The application is running in the local environment.
    require __DIR__.'/test_helper.php';
}

// para testar a api online faça, no PowerShell:
// curl.exe -u "DSapps@checkoutjuntter.com:MPfYGyiru13ibkQ6pEMT5Cxn4pCIzR3P" -H "Content-Type: application/json" 
// -X POST "https://checkout.juntter.com.br/api/webhook/paytime/new-sub-transaction" --data '{"nome":"Regis"}'