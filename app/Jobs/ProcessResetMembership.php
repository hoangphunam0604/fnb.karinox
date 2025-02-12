<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\MembershipLevel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessResetMembership implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected $customerIds;

  public function __construct(array $customerIds)
  {
    $this->customerIds = $customerIds;
  }

  public function handle()
  {
    $customers = Customer::whereIn('id', $this->customerIds)->get();
    $updates = [];

    foreach ($customers as $customer) {
      // Xác định số điểm cao nhất mà khách hàng đã đạt được trong năm qua
      $highestPoints = $customer->loyalty_points;

      // Tìm hạng thành viên mới dựa trên điểm cao nhất đạt được
      $newLevel = MembershipLevel::where('min_spent', '<=', $highestPoints)
        ->where(function ($query) use ($highestPoints) {
          $query->whereNull('max_spent')
            ->orWhere('max_spent', '>=', $highestPoints);
        })
        ->orderBy('rank', 'desc')
        ->first();

      // Chuẩn bị dữ liệu để update hàng loạt
      $updates[] = [
        'id' => $customer->id,
        'loyalty_points' => 0,
        'reward_points' => 0,
        'used_reward_points' => 0,
        'membership_level_id' => $newLevel ? $newLevel->id : $customer->membership_level_id,
        'updated_at' => now(),
      ];
    }

    // Thực hiện update hàng loạt để tối ưu
    Customer::upsert($updates, ['id'], ['loyalty_points', 'reward_points', 'used_reward_points', 'membership_level_id', 'updated_at']);
  }
}
