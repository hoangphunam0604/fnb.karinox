<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\OrderCompleted;
use App\Events\InvoiceCreated;
use App\Listeners\CreatePostPaymentPrintJobs;
use App\Listeners\CreateInvoiceListener;
use App\Listeners\CreateInvoicePrintJobs;
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
  public function boot(): void {}
}
