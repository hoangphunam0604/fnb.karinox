<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    $this->app->singleton(\App\Services\AreaService::class);
    $this->app->singleton(\App\Services\AttributeService::class);
    $this->app->singleton(\App\Services\BranchService::class);
    $this->app->singleton(\App\Services\CategoryService::class);
    $this->app->singleton(\App\Services\CustomerService::class);
    $this->app->singleton(\App\Services\InventoryService::class);
    $this->app->singleton(\App\Services\InvoiceService::class);
    $this->app->singleton(\App\Services\KitchenService::class);
    $this->app->singleton(\App\Services\MembershipUpgradeService::class);
    $this->app->singleton(\App\Services\OrderService::class);
    $this->app->singleton(\App\Services\PointService::class);
    $this->app->singleton(\App\Services\ProductService::class);
    $this->app->singleton(\App\Services\StockDeductionService::class);
    $this->app->singleton(\App\Services\SystemSettingService::class);
    $this->app->singleton(\App\Services\TableAndRoomService::class);
    $this->app->singleton(\App\Services\TaxService::class);
    $this->app->singleton(\App\Services\VoucherService::class);
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    Vite::prefetch(concurrency: 3);
  }
}
