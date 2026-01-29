<?php

use Illuminate\Support\Facades\Route;
use App\Http\POS\Controllers\BookingController;
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
use App\Http\POS\Controllers\TemplateController;

Route::middleware(['auth:api', 'is_karinox_app', 'set_karinox_branch_id'])->prefix('pos')->group(function () {
  Route::get('/tables', [TableAndRoomController::class, 'list']);
  Route::get('/products', [ProductController::class, 'index']);
  Route::get('/bookings', [BookingController::class, 'index']);
  Route::get('/orders/by-table/{table_id}', [OrderController::class, 'getOrderByTableId']);
  Route::put('/orders/{id}', [OrderController::class, 'update']);
  Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
  Route::put('/orders/{id}/remove/customer', [OrderController::class, 'removeCustomer']);
  Route::put('/orders/{id}/apply/point', [OrderController::class, 'usePoint']);
  Route::put('/orders/{id}/remove/point', [OrderController::class, 'removePoint']);
  Route::put('/orders/{id}/remove/voucher', [OrderController::class, 'removeVoucher']);
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

  Route::get('/print-templates', [TemplateController::class, 'index']);

  // Cash inventory endpoint
  Route::get('/invoices/{id}', [InvoiceController::class, 'show']);

  //Gửi lệnh in
  Route::prefix('print/{order}')->group(function () {
    Route::post('/cash-inventory', [PrintController::class, 'cashInventory']);
    Route::post('/provisional', [PrintController::class, 'provisional']);
    Route::post('/kitchen', [PrintController::class, 'kitchen']);
    Route::post('/invoice', [PrintController::class, 'invoice']);
    Route::post('/label', [PrintController::class, 'label']);
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
