<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\POS\OrderController;
use App\Http\Controllers\Api\POS\ProductController;
use App\Http\Controllers\Api\POS\CustomerController;
use App\Http\Controllers\Api\POS\Payments\CashPaymentController;
use App\Http\Controllers\Api\POS\Payments\VNPayPaymentController;
use App\Http\Controllers\Api\POS\Payments\VNPayQRController;
use App\Http\Controllers\Api\POS\TableAndRoomController;
use App\Http\Controllers\Api\POS\VoucherController;
use App\Http\Controllers\Api\POS\PrintTemplateController;


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

  Route::prefix('payments')->group(function () {
    Route::post('/cash/confirm', [CashPaymentController::class, 'confirm']);
    Route::prefix('vnpayqr')->group(function () {
      Route::post('/create', [VNPayQRController::class, 'create']);
    });
  });
});
