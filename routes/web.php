<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
  return response()->json(['code' => 200, 'messsage' => "Welcome"]);
})->name('home');

Route::get('dashboard', function () {
  return Inertia::render('Dashboard');
})->middleware(['auth:web'])->name('dashboard');

require __DIR__ . '/web-auth.php';
require __DIR__ . '/web-settings.php';
require __DIR__ . '/web-admin.php';
