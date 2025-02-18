<?php

namespace App\Providers;

use App\Services\AreaService;
use App\Services\CustomerService;
use App\Services\InvoiceService;
use App\Services\OrderService;
use App\Services\PointService;
use App\Services\SystemSettingService;
use App\Services\TaxService;
use App\Services\VoucherService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    $this->app->singleton(OrderService::class);
    $this->app->singleton(InvoiceService::class);
    $this->app->singleton(PointService::class);
    $this->app->singleton(CustomerService::class);
    $this->app->singleton(SystemSettingService::class);
    $this->app->singleton(VoucherService::class);
    $this->app->singleton(TaxService::class);
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    //
  }
}
