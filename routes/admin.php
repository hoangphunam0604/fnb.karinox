<?php

use App\Http\Controllers\Admin\ProductImportController;
use App\Http\Controllers\Admin\BranchController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/', fn() => Inertia::render('Dashboard'))->name('dashboard');
Route::resource('branches', BranchController::class);

Route::get('/abc123', fn() => Inertia::render('Dashboard'))->name('dashboard');
Route::get('/import-products', [ProductImportController::class, 'index'])->name('import-products');
Route::post('/import-products', [ProductImportController::class, 'import']);
