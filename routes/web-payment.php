<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\POS\Payments\VNPayQRController;

Route::post('/pos/payments/vnpayqr/ipn', [VNPayQRController::class, 'ipn']);
