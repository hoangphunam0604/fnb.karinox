<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
  /* ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    ) */
  ->withRouting(

    commands: __DIR__ . '/../routes/console.php',

    using: function () {

      Route::middleware('api')
        ->prefix('api')
        ->group(base_path('routes/api.php'));

      Route::middleware('web')
        ->group(base_path('routes/web.php'));

      Route::prefix('admin')
        ->name('admin.')
        ->group(base_path('routes/admin.php'));
    },

  )
  ->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
      \App\Http\Middleware\HandleInertiaRequests::class,
      \Illuminate\Session\Middleware\StartSession::class,
      \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
      \Inertia\Middleware::class, // Middleware của Inertia
    ]);

    //
  })
  ->withExceptions(function (Exceptions $exceptions) {
    //
  })->create();
