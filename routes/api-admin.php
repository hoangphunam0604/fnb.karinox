<?php

use Illuminate\Support\Facades\Route;


Route::middleware('auth')->prefix('admin')->group(function () {
  Route::apiResource('admin/print-templates', \App\Http\Controllers\Api\Admin\PrintTemplateController::class);
});
