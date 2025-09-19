<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\TableAndRoomController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\FileUploadController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\MembershipLevelController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PrintTemplateController;
use App\Http\Controllers\Admin\HolidayController;

Route::middleware(['auth:api', 'is_karinox_app', 'set_karinox_branch_id'])->prefix('admin')->group(function () {


  Route::post('upload', [FileUploadController::class, 'upload']);

  Route::apiResource('areas', AreaController::class);

  Route::apiResource('attributes', AttributeController::class);

  Route::get('branches/all', [BranchController::class, 'all']);
  Route::apiResource('branches', BranchController::class);

  Route::get('categories/all', [CategoryController::class, 'all']);
  Route::apiResource('categories', CategoryController::class);

  Route::post('customers/import', [CustomerController::class, 'import']);
  Route::apiResource('customers', CustomerController::class);

  Route::apiResource('membership-levels', MembershipLevelController::class);

  Route::apiResource('tables-and-rooms', TableAndRoomController::class);

  Route::get('invoices', [InvoiceController::class, 'index']);
  Route::get('invoices/{id}', [InvoiceController::class, 'show']);

  Route::get('products/manufacturing-autocomplete', [ProductController::class, 'manufacturingAutocomplete']);
  Route::post('products/import', [ProductController::class, 'import']);

  Route::apiResource('products', ProductController::class);
  Route::apiResource('print-templates', PrintTemplateController::class);
  Route::apiResource('holidays', HolidayController::class);
});
