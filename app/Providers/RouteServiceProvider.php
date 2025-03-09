<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
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
    RateLimiter::for('api', function (Request $request) {
      return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    $this->routes(function () {
      Route::middleware('api')
        ->prefix('api')
        ->group(base_path('routes/api.php'));

      Route::middleware('web')
        ->group(base_path('routes/admin.php'));
      Route::middleware('web')
        ->group(base_path('routes/web.php'));

      // Thêm file route mới vào hệ thống
      /* 

      // Đăng ký các file route riêng biệt
      Route::middleware(['api', 'role:admin,cashier'])
        ->prefix('api')
        ->group(base_path('routes/pos.php'));

      Route::middleware(['api', 'role:admin,kitchen_staff'])
        ->prefix('api')
        ->group(base_path('routes/kitchen.php')); */
    });
  }
}
