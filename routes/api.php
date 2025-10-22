<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\App\BranchController;

Route::get('branches', [BranchController::class, 'getUserBranches']);
Route::post('branches/select', [BranchController::class, 'selectBranch']);



require __DIR__ . '/api-auth.php';
require __DIR__ . '/api-pos.php';
require __DIR__ . '/api-admin.php';
require __DIR__ . '/api-print.php';
