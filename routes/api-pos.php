<?php

use Illuminate\Support\Facades\Route;
use App\Http\POS\Controllers\OrderController;
use App\Http\POS\Controllers\ProductController;
use App\Http\POS\Controllers\CustomerController;
use App\Http\POS\Controllers\TableAndRoomController;
use App\Http\POS\Controllers\VoucherController;
use App\Http\PaymentGateway\Controllers\InfoPlusController;
use App\Http\PaymentGateway\Controllers\VNPayController;
use App\Http\PaymentGateway\Controllers\CashController;
use App\Http\PaymentGateway\Controllers\CardController;
use App\Http\POS\Controllers\InvoiceController;
use App\Http\POS\Controllers\PrintController;
use App\Http\POS\Controllers\CashInventoryController;

Route::middleware(['auth:api', 'is_karinox_app', 'set_karinox_branch_id'])->prefix('pos')->group(function () {
  Route::get('/tables', [TableAndRoomController::class, 'list']);
  Route::get('/products', [ProductController::class, 'index']);
  Route::get('/orders/by-table/{table_id}', [OrderController::class, 'getOrderByTableId']);
  Route::put('/orders/{id}', [OrderController::class, 'update']);
  Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
  Route::put('/orders/{id}/remove-customer', [OrderController::class, 'removeCustomer']);
  Route::put('/orders/{id}/remove-reward-points-used', [OrderController::class, 'removeRewardPointsUsed']);
  Route::put('/orders/{id}/remove-voucher-used', [OrderController::class, 'removeVoucherUsed']);
  Route::post('/orders/{id}/notify-kitchen', [OrderController::class, 'notifyKitchen']);
  // Nhập bàn
  Route::post('/orders/{id}/extend', [OrderController::class, 'extend']);
  // Chia bàn
  Route::post('/orders/{id}/split', [OrderController::class, 'split']);

  Route::get('/customers', [CustomerController::class, 'index']);
  Route::post('/customers', [CustomerController::class, 'store']);
  Route::get('/customers/find', [CustomerController::class, 'findCustomer']);
  Route::post('/customers/{customer}', [CustomerController::class, 'update']);
  Route::post('/customers/{customer}/receive-new-member-gifts', [CustomerController::class, 'receiveNewMemberGift']);
  Route::post('/customers/{customer}/receive-birthday-gifts', [CustomerController::class, 'receiveBirthdayGift']);

  Route::get('/vouchers', [VoucherController::class, 'index']);

  // Cash inventory endpoint
  Route::post('/cash-inventory', [CashInventoryController::class, 'store']);
  Route::get('/invoices/{id}', [InvoiceController::class, 'show']);

  //Gửi lệnh in
  Route::prefix('print')->group(function () {
    // In tạm tính
    Route::post('/orders/{id}', [PrintController::class, 'provisional']);
    //Báo bếp
    Route::post('/kitchen/{id}', [PrintController::class, 'kitchen']);
    // Print từ Invoice (đảm bảo data chính xác 100%)
    Route::post('/invoices/{id}', [PrintController::class, 'invoice']);
  });


  Route::prefix('payments')->group(function () {
    Route::post('/cash', [CashController::class, 'pay']);
    Route::post('/card', [CardController::class, 'pay']);
    Route::post('/vnpay', [VNPayController::class, 'pay']);
    Route::post('/infoplus', [InfoPlusController::class, 'pay']);
  });
});
Route::get('/invoices/{id}/print/{brandId}/{type}', [InvoiceController::class, 'requestPrint']);


// Print Client Routes đã được thay thế bằng WebSocket + PrintService
