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

Route::middleware('auth')->group(function () {
  Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
  Route::get('/', [WelcomeController::class, 'welcome'])->name('welcome');
  Route::get('/branches', [BranchController::class, 'getUserBranches'])->name('branches.index');
  Route::post('/branches/select', [BranchController::class, 'selectBranch'])->name('branches.select');
});
Route::middleware('auth')->prefix('pos')->name('pos.')->group(function () {
  Route::get('/tables', [TableAndRoomController::class, 'list'])->name('tables');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
  Route::get('/', fn() => Inertia::render('Dashboard'))->name('dashboard');
  Route::resource('branches', AdminBranchController::class);
})->middleware(['auth', 'role:admin']);
