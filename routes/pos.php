<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\POS\OrderController;
use App\Http\Controllers\Api\POS\ProductController;
use App\Http\Controllers\Api\POS\TableAndRoomController;

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
  Route::post('/tables/update', function (Request $request) {
    $table = TableAndRoom::find($request->id);
    $table->status = $request->status;
    $table->save();

    broadcast(new TableUpdated($table));
    return response()->json($table);
  });

});
