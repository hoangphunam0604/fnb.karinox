<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ProductImportController;
use App\Http\Controllers\Admin\CustomerImportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
  Route::resource('branches', BranchController::class);
  Route::get('/products/import', [ProductImportController::class, 'viewImport'])->name('import-products');
  Route::post('/products/import', [ProductImportController::class, 'import']);
  Route::get('/customers/import', [CustomerImportController::class, 'viewImport'])->name('customers.import');
  Route::post('/customers/import', [CustomerImportController::class, 'import'])->name('customers.import');
});
