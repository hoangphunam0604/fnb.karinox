<?php

use Illuminate\Support\Facades\Route;
use App\Http\Auth\Controllers\AuthController;
use App\Http\Common\Controllers\BranchController;

Route::get('branches', [BranchController::class, 'getUserBranches']);
Route::get('branches/kiotviet', [BranchController::class, 'getKiotVietBrands']);
Route::post('branches/select', [BranchController::class, 'selectBranch']);

Route::group(['prefix' => 'auth'], function () {
  Route::post('login', [AuthController::class, 'login']);
  Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
  });
});
