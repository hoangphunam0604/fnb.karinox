<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ProductImportController;
use App\Http\Controllers\Admin\CustomerImportController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\BranchController;

Route::prefix('admin')/* ->middleware(['auth:sanctum']) */->group(function () {
  Route::get('areas', [AreaController::class, 'index']);
  Route::post('areas', [AreaController::class, 'store']);
  Route::get('areas/{id}', [AreaController::class, 'show']);
  Route::put('areas/{id}', [AreaController::class, 'update']);
  Route::delete('areas/{id}', [AreaController::class, 'destroy']);
  Route::apiResource('attributes', AttributeController::class);

  Route::apiResource('branches', BranchController::class);
});

Route::middleware('auth:web')->prefix('admin')->name('admin.')->group(function () {
  Route::resource('branches', BranchController::class);
  Route::get('/products/import', [ProductImportController::class, 'viewImport'])->name('import-products');
  Route::post('/products/import', [ProductImportController::class, 'import']);
  Route::get('/customers/import', [CustomerImportController::class, 'viewImport'])->name('customers.import');
  Route::post('/customers/import', [CustomerImportController::class, 'import'])->name('customers.import');
});
