<?php

namespace App\Jobs;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateMembershipLevelsJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Create a new job instance.
   */
  public function __construct()
  {
    //
  }

  /**
   * Execute the job.
   */
  public function handle()
  {
    /* 
    * Chỉ lấy khách hàng có số điểm đủ điều kiện lên hạng trong 24 giờ qua
    * Xử lý theo nhóm tránh quá nhiều bộ nhớ
    * Đẩy từng khách hàng vào queue để xử lý song song.
    */
    Customer::where('updated_at', '>=', now()->subDay())
      ->whereHas('membershipLevel', function ($query) {
        $query->whereColumn('customers.loyalty_points', '>=', 'membership_levels.min_spent');
      })
      ->chunk(100, function ($customers) {
        foreach ($customers as $customer) {
          dispatch(new ProcessMembershipUpdate($customer));
        }
      });
  }
}
