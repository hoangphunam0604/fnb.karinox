<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\App\BranchController;
use App\Http\Controllers\Api\POS\Payments\VNPayQRController;

Route::post('/pos/payments/vnpayqr/ipn', [VNPayQRController::class, 'ipn']);
Route::get('branches', [BranchController::class, 'getUserBranches']);
Route::middleware(['auth:api', 'is_karinox_app', 'set_karinox_branch_id'])->group(function () {
  Route::post('branches/select', [BranchController::class, 'selectBranch']);
  require __DIR__ . '/api-auth.php';
  require __DIR__ . '/api-pos.php';
  require __DIR__ . '/api-kitchen.php';
  require __DIR__ . '/api-admin.php';
});
