<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Kitchen\OrdersController;

Route::middleware(['role:kitchen'])->group(function () {});
