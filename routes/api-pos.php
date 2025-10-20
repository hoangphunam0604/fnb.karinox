<?php

use Illuminate\Support\Facades\Route;
use App\Http\POS\Controllers\OrderController;
use App\Http\POS\Controllers\ProductController;
use App\Http\POS\Controllers\CustomerController;
use App\Http\POS\Controllers\TableAndRoomController;
use App\Http\POS\Controllers\VoucherController;
use App\Http\Controllers\Payments\InfoPlusController;
use App\Http\Controllers\Payments\VNPayController;
use App\Http\Controllers\Payments\CashController;
use App\Http\POS\Controllers\PrintController;

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
  //In tạm tính
  Route::post('/orders/{id}/provisional', [PrintController::class, 'provisional']);
  //In hóa đơn
  Route::post('/orders/{id}/invoice', [PrintController::class, 'invoice']);
  //In phiếu bếp
  Route::post('/orders/{id}/kitchen', [PrintController::class, 'kitchen']);
  //In nhãn
  Route::post('/orders/{id}/labels', [PrintController::class, 'labels']);
  Route::post('/orders/{id}/auto-print', [PrintController::class, 'autoPrint']);
  Route::get('/orders/{id}/print-status', [PrintController::class, 'getPrintStatus']);

  // Print từ Invoice (đảm bảo data chính xác 100%)
  Route::post('/invoices/{id}/print', [PrintController::class, 'printFromInvoice']);
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

  Route::prefix('payments')->group(function () {
    Route::post('/cash/{code}/confirm', [CashController::class, 'confirm']);
    Route::post('/vnpay/{code}/get-qr-code', [VNPayController::class, 'getQrCode']);
    Route::post('/infoplus/{code}/get-qr-code', [InfoPlusController::class, 'getQrCode']);
  });
});

// Print Client Routes đã được thay thế bằng WebSocket + PrintService
