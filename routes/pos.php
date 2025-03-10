<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\POS\OrderController;
use App\Http\Controllers\Api\POS\TableAndRoomController;

use App\Models\TableAndRoom;
use Illuminate\Http\Request;

Route::middleware('auth:api')->prefix('pos')->group(function () {
  Route::get('/tables', [TableAndRoomController::class, 'list']);
  Route::post('/orders', [OrderController::class, 'order']);
  Route::post('/tables/update', function (Request $request) {
    $table = TableAndRoom::find($request->id);
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
