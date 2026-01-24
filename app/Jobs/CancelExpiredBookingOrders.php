<?php

namespace App\Jobs;

use App\Services\BookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CancelExpiredBookingOrders implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public function __construct()
  {
    //
  }

  public function handle(BookingService $bookingService): void
  {
    try {
      $bookingService->cancelExpiredBookingOrders();
      Log::info('Checked and cancelled expired booking orders');
    } catch (\Exception $e) {
      Log::error('Error cancelling expired booking orders: ' . $e->getMessage());
    }
  }
}
