<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\App\BranchController;
use App\Http\Controllers\Payments\VNPayController;
use App\Http\Controllers\Payments\InfoPlusController;

Route::get('branches', [BranchController::class, 'getUserBranches']);
Route::post('branches/select', [BranchController::class, 'selectBranch']);
Route::post('/payments/vnpayqr/callback', [VNPayController::class, 'callback']);
Route::post('/payments/infoplus/callback', [InfoPlusController::class, 'callback']);
require __DIR__ . '/api-auth.php';
require __DIR__ . '/api-pos.php';
require __DIR__ . '/api-admin.php';
