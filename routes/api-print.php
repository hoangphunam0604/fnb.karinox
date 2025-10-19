<?php

use App\Http\Print\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Print API Routes
|--------------------------------------------------------------------------
|
| Các routes cho hệ thống in ấn với namespace riêng biệt
| Base URL: /api/print/
|
*/

Route::middleware([
  'auth:api',
  'is_karinox_app',
  'set_karinox_branch_id'
])->prefix('print')->group(function () {

  // Print Actions
  Route::post('/provisional', [PrintController::class, 'provisional']);
  Route::post('/invoice', [PrintController::class, 'invoice']);
  Route::post('/labels', [PrintController::class, 'labels']);
  Route::post('/kitchen', [PrintController::class, 'kitchen']);
  Route::post('/auto', [PrintController::class, 'autoPrint']);

  // Queue Management
  Route::get('/queue', [PrintController::class, 'getQueue']);
  Route::put('/queue/{id}/status', [PrintController::class, 'updateStatus']);
  Route::delete('/queue/{id}', [PrintController::class, 'deleteJob']);
});

/*
|--------------------------------------------------------------------------
| Print Client API Routes (No Authentication Required)
|--------------------------------------------------------------------------
|
| Các routes cho print clients với authentication riêng biệt
| Sử dụng API keys thay vì JWT tokens
|
*/

Route::middleware(['print_client_auth'])->prefix('print/client')->group(function () {

  // Print Queue for Clients
  Route::get('/queue', [PrintController::class, 'getClientQueue']);
  Route::put('/queue/{id}/status', [PrintController::class, 'updateClientStatus']);

  // Device Registration
  Route::post('/register', [PrintController::class, 'registerDevice']);
  Route::put('/heartbeat', [PrintController::class, 'deviceHeartbeat']);

  // Print History
  Route::get('/history', [PrintController::class, 'getHistory']);
  Route::get('/history/daily', [PrintController::class, 'getDailyHistory']);
});
