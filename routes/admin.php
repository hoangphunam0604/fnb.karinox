<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/abc123', fn() => Inertia::render('Dashboard'))->name('dashboard');
