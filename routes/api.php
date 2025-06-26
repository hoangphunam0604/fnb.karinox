<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\App\BranchController;
use App\Http\Controllers\Api\POS\Payments\VNPayQRController;

Route::get('branches', [BranchController::class, 'getUserBranches']);
Route::post('branches/select', [BranchController::class, 'selectBranch']);
Route::post('/payments/vnpayqr/ipn', [VNPayQRController::class, 'ipn']);
Route::post('/pos/payments/vnpayqr/ipn', [VNPayQRController::class, 'ipn']);
require __DIR__ . '/api-auth.php';
require __DIR__ . '/api-pos.php';
require __DIR__ . '/api-admin.php';
