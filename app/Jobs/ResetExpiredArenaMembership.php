<?php

namespace App\Jobs;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

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

    // Tìm các khách hàng có arena_member_exp < today và arena_member != 'none'
    $expiredCustomers = Customer::where('arena_member', '!=', 'none')
      ->whereNotNull('arena_member')
      ->whereNotNull('arena_member_exp')
      ->where('arena_member_exp', '<', $today)
      ->get();

    $resetCount = 0;

    foreach ($expiredCustomers as $customer) {
      $oldMemberType = $customer->arena_member;
      $expiredDate = $customer->arena_member_exp;

      // Reset về none
      $customer->arena_member = 'none';
      $customer->save();

      $resetCount++;

      Log::info('Arena membership expired and reset', [
        'customer_id' => $customer->id,
        'customer_name' => $customer->fullname,
        'old_member_type' => $oldMemberType,
        'expired_date' => $expiredDate->format('Y-m-d'),
      ]);
    }

    Log::info('Reset expired arena memberships completed', [
      'date' => $today->format('Y-m-d'),
      'reset_count' => $resetCount
    ]);
  }
}
