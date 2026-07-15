<?php

use Illuminate\Support\Facades\Route;

Route::get('/{path?}', fn () => response()->file(public_path('portfolio.html')))
    ->where('path', '^(?!api(?:/|$)).*');
