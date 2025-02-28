<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

Route::get('/login', fn() => Inertia::render('Auth/Login'));
Route::get('/register', fn() => Inertia::render('Auth/Register'));

Route::get('/', function () {
  return Inertia::render('Welcome', [
    'canLogin' => Route::has('login'),
    'canRegister' => Route::has('register'),
    'laravelVersion' => Application::VERSION,
    'phpVersion' => PHP_VERSION,
  ]);
});

Route::get('/dashboard', function () {
  return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

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
})->middleware(['auth', 'role:manager']);

require __DIR__ . '/auth.php';

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
