<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\POS\OrderController;
use App\Http\Controllers\Api\POS\TableController;

Route::prefix('pos')->group(function () {
  Route::get('/tables', [TableController::class, 'index']);
  Route::post('/orders', [OrderController::class, 'order']);
  /* 
  Route::post('/orders/prev', [OrdersController::class, 'preOrder']);
  Route::post('/orders/use-voucher', [OrdersController::class, 'useVoucher']);
  Route::post('/orders/use-point', [OrdersController::class, 'usePoint']); */
});
