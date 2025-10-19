<?php

use Illuminate\Support\Facades\Route;
use App\Http\POS\Controllers\OrderController;
use App\Http\POS\Controllers\ProductController;
use App\Http\POS\Controllers\CustomerController;
use App\Http\POS\Controllers\TableAndRoomController;
use App\Http\POS\Controllers\VoucherController;
use App\Http\POS\Controllers\PrintTemplateController;
use App\Http\Controllers\Payments\InfoPlusController;
use App\Http\Controllers\Payments\VNPayController;
use App\Http\Controllers\Payments\CashController;
use App\Http\Print\Controllers\PrintController;

Route::middleware(['auth:api', 'is_karinox_app', 'set_karinox_branch_id'])->prefix('pos')->group(function () {
  Route::get('/tables', [TableAndRoomController::class, 'list']);
  Route::get('/products', [ProductController::class, 'index']);
  Route::get('/orders', [OrderController::class, 'index']);
  Route::post('/orders', [OrderController::class, 'getOrderByTableId']);
  Route::put('/orders/{id}', [OrderController::class, 'update']);
  Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
  Route::put('/orders/{id}/remove-customer', [OrderController::class, 'removeCustomer']);
  Route::put('/orders/{id}/remove-reward-points-used', [OrderController::class, 'removeRewardPointsUsed']);
  Route::put('/orders/{id}/remove-voucher-used', [OrderController::class, 'removeVoucherUsed']);
  Route::post('/orders/{id}/notify-kitchen', [OrderController::class, 'notifyKitchen']);
  Route::post('/orders/{id}/provisional', [OrderController::class, 'provisional']);
  Route::post('/orders/{id}/extend', [OrderController::class, 'extend']);
  Route::post('/orders/{id}/split', [OrderController::class, 'split']);

  Route::get('/customers', [CustomerController::class, 'index']);
  Route::post('/customers', [CustomerController::class, 'store']);
  Route::get('/customers/find', [CustomerController::class, 'findCustomer']);
  Route::post('/customers/{customer}', [CustomerController::class, 'update']);
  Route::post('/customers/{customer}/receive-new-member-gifts', [CustomerController::class, 'receiveNewMemberGift']);
  Route::post('/customers/{customer}/receive-birthday-gifts', [CustomerController::class, 'receiveBirthdayGift']);

  Route::get('/vouchers', [VoucherController::class, 'index']);

  Route::get('print-templates', [PrintTemplateController::class, 'index']);

  // Print routes (cần authentication)
  Route::prefix('print')->group(function () {
    Route::post('/provisional', [PrintController::class, 'provisional']);
    Route::post('/invoice', [PrintController::class, 'invoice']);
    Route::post('/labels', [PrintController::class, 'labels']);
    Route::post('/kitchen', [PrintController::class, 'kitchen']);
    Route::post('/auto', [PrintController::class, 'autoPrint']);
    Route::get('/order/{order}/status', [PrintController::class, 'getOrderPrintStatus']);
    Route::get('/preview', [PrintController::class, 'preview']);
  });

  Route::prefix('payments')->group(function () {
    Route::post('/cash/{code}/confirm', [CashController::class, 'confirm']);
    Route::post('/vnpay/{code}/get-qr-code', [VNPayController::class, 'getQrCode']);
    Route::post('/infoplus/{code}/get-qr-code', [InfoPlusController::class, 'getQrCode']);
  });
});

// Print Client Routes (không cần user authentication, dùng device authentication)
Route::middleware(['print_client_auth'])->prefix('pos/print-client')->group(function () {
  Route::get('/queue', [PrintController::class, 'getQueue']);
  Route::post('/queue/{job}/processed', [PrintController::class, 'markProcessed']);
  Route::post('/queue/{job}/failed', [PrintController::class, 'markFailed']);
  Route::post('/queue/{job}/retry', [PrintController::class, 'retryJob']);
  Route::get('/device/status', [PrintController::class, 'getDeviceStatus']);
});
