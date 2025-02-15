<?php

namespace App\Jobs\Tasks;

use App\Models\Customer;
use App\Models\MembershipLevel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnnualPointsAndMembershipProcess implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected array $customerIds;

  public function __construct(array $customerIds)
  {
    $this->customerIds = $customerIds;
  }

  public function handle()
  {
    $customers = Customer::whereIn('id', $this->customerIds)->get();
    //$updates = [];

    foreach ($customers as $customer) {

      $loyaltyPoints = max($customer->loyalty_points, 0);
      $newLevel = MembershipLevel::where('min_spent', '<=', $loyaltyPoints)
        ->where(function ($query) use ($loyaltyPoints) {
          $query->whereNull('max_spent')
            ->orWhere('max_spent', '>=', $loyaltyPoints);
        })
        ->orderBy('rank', 'desc')
        ->first();
      /**
       * Cập nhật từng khách hàng
       * 
       * Phương án này không tối ưu tốc độ truy vấn nhưng upsert không dùng được nên đành chấp nhận
       */
      $customer->loyalty_points = 0;
      $customer->reward_points = 0;
      $customer->used_reward_points = 0;
      $customer->membership_level_id = $newLevel ? $newLevel->id : $customer->membership_level_id;
      $customer->save();

      /* $updates[] = [
        'id' => $customer->id,
        'loyalty_points' => 0,
        'reward_points' => 0,
        'used_reward_points' => 0,
        'membership_level_id' => $newLevel ? $newLevel->id : $customer->membership_level_id,
        'updated_at' => now(),
      ]; */
    }

    // Hàm upsert luôn trả về insert chứ không update nên không thể dùng được
    //Customer::upsert($updates, ['id'], ['loyalty_points', 'reward_points', 'used_reward_points', 'membership_level_id', 'updated_at']);
  }
}
