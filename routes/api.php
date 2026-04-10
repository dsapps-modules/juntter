<?php

use App\Http\Controllers\PaytimeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/paytime', [PaytimeWebhookController::class, 'handle']);

if (app()->environment('local')) {
    require __DIR__.'/test_helper.php';
}
