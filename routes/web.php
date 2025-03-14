<?php

use App\Http\Controllers\pos\TableAndRoomController;
use App\Http\Controllers\App\BranchController;
use App\Http\Controllers\Admin\BranchController as AdminBranchController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/* Route::middleware([RedirectIfAuthenticated::class])->group(function () { */

Route::get('/login', [AuthController::class, 'loginView'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login');
/* }); */

Route::get('/', [WelcomeController::class, 'welcome'])->name('welcome');
Route::middleware('auth')->group(function () {
  Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});


require_once __DIR__ . "/api.php";
