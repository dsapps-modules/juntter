<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaytimeWebhookController;

Route::post('/webhook/paytime/update-establishment-status', [PaytimeWebhookController::class, 'updateEstablishmentStatus']);
Route::post('/webhook/paytime/update-billet-status', [PaytimeWebhookController::class, 'updateBilletStatus']);
Route::post('/webhook/paytime/create-establishment', [PaytimeWebhookController::class, 'createEstablishment']);