<?php

use App\Http\Controllers\CoffeePaymentController;
use App\Http\Controllers\DarajaCallbackController;
use App\Http\Controllers\OrganizationCallbackController;
use Illuminate\Support\Facades\Route;

Route::post('/coffee-payments', [CoffeePaymentController::class, 'store'])->middleware('throttle:coffee-initiate');
Route::get('/coffee-payments/{payment:public_id}', [CoffeePaymentController::class, 'show'])->middleware('throttle:coffee-status');
Route::post('/mpesa/stk/callback', DarajaCallbackController::class)->middleware('throttle:daraja-callback');
Route::post('/mpesa/b2c/result', [OrganizationCallbackController::class, 'b2cResult'])->middleware('throttle:daraja-callback');
Route::post('/mpesa/b2c/timeout', [OrganizationCallbackController::class, 'b2cTimeout'])->middleware('throttle:daraja-callback');
Route::post('/mpesa/balance/result', [OrganizationCallbackController::class, 'balanceResult'])->middleware('throttle:daraja-callback');
Route::post('/mpesa/balance/timeout', [OrganizationCallbackController::class, 'balanceTimeout'])->middleware('throttle:daraja-callback');
