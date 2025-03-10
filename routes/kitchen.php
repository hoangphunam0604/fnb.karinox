<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Kitchen\OrdersController;

Route::prefix('kitchen')->middleware(['role:kitchen'])->group(function () {
  Route::get('list', function () {
    return response()->json(['success' => true, 'msg' => "kitchen"]);
  });
});
