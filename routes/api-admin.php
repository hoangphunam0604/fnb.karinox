<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\AttributeController;

Route::middleware('auth')->prefix('admin')->group(function () {
  Route::apiResource('admin/print-templates', \App\Http\Controllers\Api\Admin\PrintTemplateController::class);
  Route::get('areas', [AreaController::class, 'index']);
  Route::post('areas', [AreaController::class, 'store']);
  Route::get('areas/{id}', [AreaController::class, 'show']);
  Route::put('areas/{id}', [AreaController::class, 'update']);
  Route::delete('areas/{id}', [AreaController::class, 'destroy']);
  Route::apiResource('attributes', AttributeController::class);
});
