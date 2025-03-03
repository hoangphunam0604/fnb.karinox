<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\POS\OrdersController;
use App\Http\Controllers\Api\POS\TablesController;

Route::prefix('pos')->group(function () {
  Route::get('/tables', [TablesController::class, 'index']);
  Route::post('/orders', [OrdersController::class, 'store']);
  Route::post('/orders/prev', [OrdersController::class, 'preOrder']);
  Route::post('/orders/use-voucher', [OrdersController::class, 'useVoucher']);
  Route::post('/orders/use-point', [OrdersController::class, 'usePoint']);
});
