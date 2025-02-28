<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Models\Branch;
use App\Models\TableAndRoom;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', function (Request $request) {
  $request->validate([
    'email' => 'required|email',
    'password' => 'required',
  ]);

  $user = User::where('email', $request->email)->first();

  if (! $user || ! Hash::check($request->password, $user->password)) {
    throw ValidationException::withMessages([
      'email' => ['Thông tin đăng nhập không đúng.'],
    ]);
  }

  return response()->json([
    'token' => $user->createToken('POS-Token')->plainTextToken,
    'user' => $user
  ]);
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
  return $request->user();
});


Route::get('/branches', function () {
  return Branch::all();
});
Route::group('/POS', function () {
  Route::get('/tables-and-rooms', function () {
    return TableAndRoom::all();
  });
});
