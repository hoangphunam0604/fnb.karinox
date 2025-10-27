<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

Route::get('/', function () {
  return response()->json(['code' => 200, 'messsage' => "Welcome"]);
})->name('home');

// Broadcasting authentication routes (without /api prefix)
Route::middleware('auth:api')->group(function () {
  Broadcast::routes();
});

require __DIR__ . '/payment-gateway.php';
