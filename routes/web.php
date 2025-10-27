<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  return response()->json(['code' => 200, 'messsage' => "Welcome"]);
})->name('home');
require __DIR__ . '/payment-gateway.php';
