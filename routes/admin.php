<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ProductImportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
  Route::resource('branches', BranchController::class);
  Route::get('/import-products', [ProductImportController::class, 'index'])->name('import-products');
  Route::post('/import-products', [ProductImportController::class, 'import']);
});
