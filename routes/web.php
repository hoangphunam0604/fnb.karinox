<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
  return Inertia::render('Welcome');
})->name('home');
Route::get('/test', function () {
  $now = \Carbon\Carbon::parse('2025-01-13 01:00:00');
  $dayOfWeek = $now->dayOfWeek;
  $data =  ceil($now->day / 7);
  return response()->json(['data' => $data, 'dayOfWeek' => $dayOfWeek, 'now' => $now->format('Y-m-d')]);
});
Route::get('dashboard', function () {
  return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/admin.php';
