<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\POS\OrderController;
use App\Http\Controllers\Api\POS\TableController;

use App\Events\TableUpdated;

Route::prefix('pos')->group(function () {
  Route::get('/tables', [TableController::class, 'index']);
  Route::post('/orders', [OrderController::class, 'order']);

  Route::post('/tables/update', function (Request $request) {
    $table = Table::find($request->id);
    $table->status = $request->status;
    $table->save();

    broadcast(new TableUpdated($table));
    return response()->json($table);
  });

  /* 
  Route::post('/orders/prev', [OrdersController::class, 'preOrder']);
  Route::post('/orders/use-voucher', [OrdersController::class, 'useVoucher']);
  Route::post('/orders/use-point', [OrdersController::class, 'usePoint']); */
});
