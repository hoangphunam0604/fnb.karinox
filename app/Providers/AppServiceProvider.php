<?php

namespace App\Providers;

use App\Services\CustomerService;
use App\Services\PointService;
use App\Services\SystemSettingService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    $this->app->singleton(PointService::class);
    $this->app->singleton(CustomerService::class);
    $this->app->singleton(SystemSettingService::class);
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    //
  }
}
