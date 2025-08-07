<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaytimeWebhookController;

Route::post('/webhook/paytime/update-establishment-status', [PaytimeWebhookController::class, 'updateEstablishmentStatus']);
Route::post('/webhook/paytime/update-billet-status', [PaytimeWebhookController::class, 'updateBilletStatus']);