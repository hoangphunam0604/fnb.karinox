<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\TableAndRoomController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\FileUploadController;
use App\Http\Controllers\Admin\ProductController;

Route::middleware(['auth:api', 'is_karinox_app', 'set_karinox_branch_id'])->prefix('admin')->group(function () {
  Route::apiResource('admin/print-templates', \App\Http\Controllers\Api\Admin\PrintTemplateController::class);
  Route::post('upload', [FileUploadController::class, 'upload']);
  Route::get('areas', [AreaController::class, 'index']);
  Route::post('areas', [AreaController::class, 'store']);
  Route::get('areas/{id}', [AreaController::class, 'show']);
  Route::put('areas/{id}', [AreaController::class, 'update']);
  Route::delete('areas/{id}', [AreaController::class, 'destroy']);
  Route::apiResource('tables-and-rooms', TableAndRoomController::class);
  Route::apiResource('attributes', AttributeController::class);
  Route::get('branches/all', [BranchController::class, 'all']);
  Route::apiResource('branches', BranchController::class);

  Route::get('categories/all', [CategoryController::class, 'all']);
  Route::apiResource('categories', CategoryController::class);
  Route::get('products/manufacturing-autocomplete', [ProductController::class, 'manufacturingAutocomplete']);
  Route::apiResource('products', ProductController::class);
});
