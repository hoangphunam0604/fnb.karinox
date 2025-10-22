<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Payments\VNPayController;
use App\Http\Controllers\Payments\InfoPlusController;

Route::get('/', function () {
  return response()->json(['code' => 200, 'messsage' => "Welcome"]);
})->name('home');


Route::post('/payments/vnpayqr/callback', [VNPayController::class, 'callback']);
Route::post('/payments/infoplus/callback', [InfoPlusController::class, 'callback']);

// require __DIR__ . '/web-auth.php'; // Tạm thời comment để tránh lỗi AuthenticatedSessionController
require __DIR__ . '/web-settings.php';
require __DIR__ . '/web-admin.php';
