<?php

use App\Http\Controllers\pos\TableAndRoomController;
use App\Http\Controllers\App\BranchController;
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
})->middleware(['auth', 'role:admin']);
/* 
Route::middleware('auth')->group(function () {
  Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
  Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
  Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Panel
Route::prefix('admin')->group(function () {
  Route::get('/', fn() => Inertia::render('Dashboard'));
  Route::get('/users', fn() => Inertia::render('Users'));
  Route::get('/products', fn() => Inertia::render('Products'));
})->middleware(['auth', 'role:admin']);

// POS (Thu ngân)
Route::prefix('pos')->group(function () {
  Route::get('/tables', fn() => Inertia::render('TablesAndRooms'));
})->middleware(['auth', 'role:cashier']);

// Kitchen (Bếp)
Route::prefix('kitchen')->group(function () {
  Route::get('/', fn() => Inertia::render('KitchenDashboard'));
  Route::get('/queue', fn() => Inertia::render('OrderQueue'));
})->middleware(['auth', 'role:kitchen']);

// Manager (Quản lý)
Route::prefix('manager')->group(function () {
  Route::get('/', fn() => Inertia::render('Reports'));
  Route::get('/sales', fn() => Inertia::render('Sales'));
})->middleware(['auth', 'role:manager']); */

require __DIR__ . '/auth.php';
