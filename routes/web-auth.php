<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:web')->group(function () {
  Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ->name('login');

  Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth:web')->group(function () {
  Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');
});
