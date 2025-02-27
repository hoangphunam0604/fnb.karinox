<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Models\Branch;
use App\Models\TableAndRoom;

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
