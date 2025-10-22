<?php

use App\Http\Print\Controllers\PrintController;
use App\Http\Controllers\Print\TemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Print Service API Routes
|--------------------------------------------------------------------------
|
| Routes cho ứng dụng quản lý máy in tại chi nhánh
| Không cần authentication vì đã có bảo mật qua connection_code
|
*/

// API cho ứng dụng quản lý máy in (không cần auth)
Route::prefix('print')->group(function () {
  // Kết nối ban đầu
  Route::post('connect', [PrintController::class, 'connect']);

  // Xác nhận đã in thành công  
  Route::post('confirm', [PrintController::class, 'confirm']);

  // Báo lỗi in
  Route::post('error', [PrintController::class, 'reportError']);

  // Lịch sử in
  Route::get('history', [PrintController::class, 'history']);

  // Thống kê in
  Route::get('stats', [PrintController::class, 'stats']);

  // Template management for print app
  Route::get('templates', [TemplateController::class, 'index']);
});
