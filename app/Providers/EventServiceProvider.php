<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
  /**
   * The event to listener mappings for the application.
   *
   * @var array<class-string, array<int, class-string>>
   */
  protected $listen = [
    Registered::class => [
      SendEmailVerificationNotification::class,
    ],
    \App\Events\InvoiceCompleted::class => [
      \App\Listeners\InvoiceCompletedProcess::class,
    ],
    \App\Events\InvoiceCancelled::class => [
      \App\Listeners\InvoiceCancelledProcess::class,
    ],
    \App\Events\OrderUpdated::class => [
      \App\Listeners\SendNewOrderItemsToKitchen::class,
    ],
    \App\Events\OrderCompleted::class => [
      \App\Listeners\SendNewOrderItemsToKitchen::class,
      \App\Listeners\CreateInvoiceListener::class,
    ],

  ];

  /**
   * Register any events for your application.
   */
  public function boot(): void
  {
    //
  }

  /**
   * Determine if events and listeners should be automatically discovered.
   */
  public function shouldDiscoverEvents(): bool
  {
    return false;
  }
}
