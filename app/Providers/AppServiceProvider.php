<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\OrderCompleted;
use App\Events\InvoiceCreated;
use App\Listeners\CreatePostPaymentPrintJobs;
use App\Listeners\CreateInvoiceListener;
use App\Listeners\DeductStockAfterInvoice;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    // Laravel auto-discovery sẽ tự động tìm và đăng ký listeners
    // Không cần đăng ký thủ công nữa
  }
}
