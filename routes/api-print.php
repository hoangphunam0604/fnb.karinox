<?php

use App\Http\Print\Controllers\BranchController;
use App\Http\Print\Controllers\PrintDataController;
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

  // API chính: Lấy data in theo type và id
  Route::get('data/{type}/{id}', [PrintDataController::class, 'getData']);

  // Lấy danh sách hóa đơn đã được yêu cầu in
  Route::get('invoices/print-requested', [PrintDataController::class, 'getPrintRequestedInvoices']);
});
