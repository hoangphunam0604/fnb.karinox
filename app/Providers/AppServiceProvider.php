<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\OrderCompleted;
use App\Listeners\CreatePostPaymentPrintJobs;

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
    // Đăng ký listener cho OrderCompleted event
    Event::listen(
      OrderCompleted::class,
      CreatePostPaymentPrintJobs::class,
    );
  }
}
