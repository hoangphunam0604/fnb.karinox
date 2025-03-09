<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
  /**
   * The path to your application's "home" route.
   *
   * Typically, users are redirected here after authentication.
   *
   * @var string
   */
  public const HOME = '/home';

  /**
   * Define your route model bindings, pattern filters, and other route configuration.
   */
  public function boot(): void
  {
    // 🚨 Debug dữ liệu ngay tại middleware
    logger()->info('Boot method called');

    RateLimiter::for('api', function (Request $request) {
      return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });
    Log::info('Before route definition'); // Thêm log trước khi load route

    $this->routes(function () {
      Route::middleware('api')
        ->prefix('api')
        ->group(function () {
          require base_path('routes/api.php');
          Log::info('API routes loaded');
        });

      Route::middleware('web')
        ->group(base_path('routes/web.php'));
    });
  }
}
