<?php 

use Illuminate\Support\Facades\Route;

// just to serve as a test point
Route::post('/ping', fn() => response()->json(['pong' => true]));