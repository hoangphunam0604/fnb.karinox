<?php

namespace App\Jobs;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ResetExpiredArenaMembership implements ShouldQueue
{
  use Queueable;

  /**
   * Create a new job instance.
   */
  public function __construct()
  {
    //
  }

  /**
   * Execute the job.
   * Reset gói arena member của các khách hàng đã hết hạn
   */
  public function handle(): void
  {
    $today = Carbon::today();

    // Tìm các hội viên không phải master đã hết hạn
    $expiredCustomers = Customer::whereNotIn('arena_member', ['none', 'master'])
      ->whereNotNull('arena_member')
      ->whereNotNull('arena_member_exp')
      ->where('arena_member_exp', '<', $today)
      ->get();

    $resetCount = 0;

    foreach ($expiredCustomers as $customer) {
      $customer->arena_member = 'none';
      $customer->save();
      $resetCount++;
    }
  }
}
