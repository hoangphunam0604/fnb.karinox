<?php

use App\Http\Print\Controllers\PrintController;
use App\Http\Print\Controllers\PrintTemplateController;
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

/*
|--------------------------------------------------------------------------
| Print Management App Routes (No Login Required)
|--------------------------------------------------------------------------
|
| Routes cho ứng dụng quản lý in độc lập
| Chỉ cần X-Branch-ID header để xác định chi nhánh
|
*/

Route::middleware(['print_app_auth'])->prefix('print')->group(function () {

  // Print Actions (if needed from management app)
  Route::post('/provisional', [PrintController::class, 'provisional']);
  Route::post('/invoice', [PrintController::class, 'invoice']);
  Route::post('/labels', [PrintController::class, 'labels']);
  Route::post('/kitchen', [PrintController::class, 'kitchen']);
  Route::post('/auto', [PrintController::class, 'autoPrint']);

  // Test Print
  Route::post('/test', [PrintController::class, 'testPrint']);

  // Queue Management
  Route::get('/queue', [PrintController::class, 'getQueue']);
  Route::put('/queue/{id}/status', [PrintController::class, 'updateStatus']);
  Route::delete('/queue/{id}', [PrintController::class, 'deleteJob']);

  // Template Management
  Route::get('/templates', [PrintTemplateController::class, 'index']);
  Route::get('/templates/{id}', [PrintTemplateController::class, 'show']);
  Route::post('/templates', [PrintTemplateController::class, 'store']);
  Route::put('/templates/{id}', [PrintTemplateController::class, 'update']);
  Route::delete('/templates/{id}', [PrintTemplateController::class, 'destroy']);
  Route::post('/templates/{id}/duplicate', [PrintTemplateController::class, 'duplicate']);
  Route::post('/templates/{id}/set-default', [PrintTemplateController::class, 'setDefault']);
  Route::post('/templates/{id}/preview', [PrintTemplateController::class, 'preview']);

  // Branch Selection & Settings
  Route::get('/branches', [PrintController::class, 'getBranches']);
  Route::post('/branch/select', [PrintController::class, 'selectBranch']);
  Route::get('/settings', [PrintController::class, 'getSettings']);
  Route::post('/settings', [PrintController::class, 'updateSettings']);
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

  // Test Print for Clients (no auth required)
  Route::post('/test', [PrintController::class, 'testPrint']);

  // Templates for Clients (read-only)
  Route::get('/templates', [PrintTemplateController::class, 'index']);
  Route::get('/templates/{id}', [PrintTemplateController::class, 'show']);

  // Device Registration
  Route::post('/register', [PrintController::class, 'registerDevice']);
  Route::put('/heartbeat', [PrintController::class, 'deviceHeartbeat']);

  // Print History
  Route::get('/history', [PrintController::class, 'getHistory']);
  Route::get('/history/daily', [PrintController::class, 'getDailyHistory']);
});
