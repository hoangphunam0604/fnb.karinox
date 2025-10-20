<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\App\BranchController;

Route::get('branches', [BranchController::class, 'getUserBranches']);
Route::post('branches/select', [BranchController::class, 'selectBranch']);

// Print API routes (Event-driven via Socket)
Route::middleware(['auth:api'])->group(function () {
  Route::prefix('print')->group(function () {
    Route::post('confirm', [\App\Http\Controllers\Api\PrintController::class, 'confirmPrinted']);
    Route::post('error', [\App\Http\Controllers\Api\PrintController::class, 'reportError']);
    Route::get('history', [\App\Http\Controllers\Api\PrintController::class, 'history']);
    Route::get('stats', [\App\Http\Controllers\Api\PrintController::class, 'stats']);
    Route::post('manual', [\App\Http\Controllers\Api\PrintController::class, 'manualPrint']);
  });
});

require __DIR__ . '/api-auth.php';
require __DIR__ . '/api-pos.php';
require __DIR__ . '/api-admin.php';
