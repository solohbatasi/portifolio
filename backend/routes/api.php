<?php

use App\Http\Controllers\CoffeePaymentController;
use App\Http\Controllers\DarajaCallbackController;
use Illuminate\Support\Facades\Route;

Route::post('/coffee-payments', [CoffeePaymentController::class, 'store'])->middleware('throttle:coffee-initiate');
Route::get('/coffee-payments/{payment:public_id}', [CoffeePaymentController::class, 'show'])->middleware('throttle:coffee-status');
Route::post('/mpesa/stk/callback', DarajaCallbackController::class)->middleware('throttle:daraja-callback');
