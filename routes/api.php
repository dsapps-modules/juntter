<?php

use App\Http\Controllers\PaytimeWebhookController;
use App\Http\Controllers\Spa\EstablishmentOverviewController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/paytime', [PaytimeWebhookController::class, 'handle']);
Route::get('/spa/estabelecimentos', EstablishmentOverviewController::class);

if (app()->environment('local')) {
    require __DIR__.'/test_helper.php';
}
