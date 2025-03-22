<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\POS\OrderController;
use App\Http\Controllers\Api\POS\ProductController;
use App\Http\Controllers\Api\POS\CustomerController;
use App\Http\Controllers\Api\POS\TableAndRoomController;
use App\Http\Controllers\Api\POS\VoucherController;

use App\Models\TableAndRoom;
use App\Services\OrderService;
use Illuminate\Http\Request;

Route::get('/test-order-service', function (OrderService $orderService) {
  return response()->json(['message' => 'OrderService injected OK']);
});

Route::middleware('auth:api')->prefix('pos')->group(function () {
  Route::get('/tables', [TableAndRoomController::class, 'list']);
  Route::get('/products', [ProductController::class, 'index']);
  Route::get('/orders', [OrderController::class, 'index']);
  Route::post('/orders', [OrderController::class, 'getOrderByTableId']);
  Route::put('/orders/{id}', [OrderController::class, 'update']);
  Route::post('/orders/{id}/checkout', [OrderController::class, 'checkout']);
  Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
  Route::put('/orders/{id}/remove-customer', [OrderController::class, 'removeCustomer']);
  Route::put('/orders/{id}/remove-reward-points-used', [OrderController::class, 'removeRewardPointsUsed']);
  Route::put('/orders/{id}/remove-voucher-used', [OrderController::class, 'removeVoucherUsed']);

  Route::get('/customers', [CustomerController::class, 'index']);
  Route::get('/customers/find', [CustomerController::class, 'findCustomer']);

  Route::get('/vouchers', [VoucherController::class, 'index']);

  Route::post('/tables/update', function (Request $request) {
    $table = TableAndRoom::find($request->id);
    $table->status = $request->status;
    $table->save();

    broadcast(new TableUpdated($table));
    return response()->json($table);
  });
});
