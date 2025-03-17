<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\App\BranchController;

Route::get('/welcome', function () {
  return response()->json(['success' => true, 'msg' => "Welcome"]);
});

Route::get('/welcome', function () {
  return response()->json(['success' => true, 'msg' => "Welcome"]);
});
Route::group(['prefix' => 'auth'], function () {
  Route::post('login', [AuthController::class, 'login']);
  Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);

  });
});

Route::middleware('auth:api')->group(function () {  
  Route::get('branches', [BranchController::class, 'getUserBranches']);
  Route::post('branches/select', [BranchController::class, 'selectBranch']);
});
require __DIR__ . '/pos.php';
