<?php

use App\Http\Print\Controllers\BranchController;
use App\Http\Print\Controllers\HistoryController;
use App\Http\Print\Controllers\InvoiceController;
use App\Http\Print\Controllers\TemplateController;
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
  Route::post('/branchs/{connection_code}/connect', [BranchController::class, 'connect']);

  // Danh sách mẫu in
  Route::get('templates', [TemplateController::class, 'index']);

  // Lấy thông tin in cho invoice (frontend gọi sau khi nhận WebSocket)
  Route::get('invoices/{invoice}/print-data', [InvoiceController::class, 'getPrintData']);

  Route::prefix('histories')->group(function () {
    // Lịch sử in
    Route::get('', [HistoryController::class, 'index']);
    // Xác nhận đã in thành công  
    Route::post('{printHistory}/confirm', [HistoryController::class, 'confirm']);

    // Báo lỗi in
    Route::post('{printHistory}/error', [HistoryController::class, 'reportError']);
  });
});
