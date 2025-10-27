<?php

use Illuminate\Support\Facades\Route;
use App\Http\PaymentGateway\Controllers\VNPayController;
use App\Http\PaymentGateway\Controllers\InfoPlusController;

Route::post('/payments/vnpayqr/callback', [VNPayController::class, 'callback']);
Route::post('/payments/infoplus/callback', [InfoPlusController::class, 'callback']);
