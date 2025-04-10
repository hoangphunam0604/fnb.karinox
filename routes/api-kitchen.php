<?php

use App\Http\Controllers\Api\Kitchen\KitchenItemController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:api')->prefix('kitchen')->group(function () {

  Route::get('items', [KitchenItemController::class, 'index']);
  Route::post('items/completed', [KitchenItemController::class, 'completedItems']);
  Route::post('items/{id}/processing', [KitchenItemController::class, 'processing']);
  Route::post('items/{id}/completed', [KitchenItemController::class, 'completed']);
  Route::post('items/{id}/cancel', [KitchenItemController::class, 'completed']);
});
