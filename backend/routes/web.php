<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::view('/admin/login', 'admin.login')->middleware('guest')->name('admin.login');
Route::view('/admin/access-denied', 'admin.denied')->name('admin.denied');
Route::get('/admin/logout', fn () => abort(405));
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->middleware('guest')->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->middleware('guest')->name('auth.google.callback');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'allowed.admin'])->group(function (): void {
    Route::post('/logout', [GoogleAuthController::class, 'logout'])->name('logout');
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/transactions', [AdminController::class, 'transactions'])->name('transactions');
    Route::get('/transactions/{payment:public_id}', [AdminController::class, 'transaction'])->name('transactions.show');
    Route::post('/transactions/{payment:public_id}/refresh', [AdminController::class, 'refreshTransaction'])->middleware('throttle:admin-sensitive')->name('transactions.refresh');
    Route::get('/payouts', [AdminController::class, 'payouts'])->name('payouts');
    Route::get('/payouts/{payout:public_id}', [AdminController::class, 'payout'])->name('payouts.show');
    Route::post('/payouts', [AdminController::class, 'createPayout'])->middleware('throttle:admin-sensitive')->name('payouts.store');
    Route::get('/balance', [AdminController::class, 'balance'])->name('balance');
    Route::post('/balance/refresh', [AdminController::class, 'refreshBalance'])->middleware('throttle:admin-sensitive')->name('balance.refresh');
});

Route::get('/{path?}', fn () => response()->file(public_path('portfolio.html')))
    ->where('path', '^(?!api(?:/|$)).*');
