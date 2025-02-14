<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MembershipLevel;
use App\Models\MembershipUpgradeHistory;
use Illuminate\Support\Facades\DB;

class MembershipUpgradeService
{
  public function upgradeMembership(Customer $customer, MembershipLevel $newLevel)
  {
    return DB::transaction(function () use ($customer, $newLevel) {
      // Lấy hạng cũ của khách hàng
      $oldLevel = $customer->membershipLevel;

      // Kiểm tra nếu khách hàng đã đạt hạng này trước đó
      $existingUpgrade = MembershipUpgradeHistory::where('customer_id', $customer->id)
        ->where('new_membership_level_id', $newLevel->id)
        ->exists();

      if ($existingUpgrade) {
        throw new \Exception("Khách hàng đã được thăng hạng và nhận quà trước đó!");
      }

      // Cập nhật hạng thành viên mới
      $customer->update(['membership_level_id' => $newLevel->id]);

      // Lưu lịch sử thăng hạng
      $history = MembershipUpgradeHistory::create([
        'customer_id' => $customer->id,
        'old_membership_level_id' => optional($oldLevel)->id,
        'new_membership_level_id' => $newLevel->id,
        'upgrade_reward_content' => $newLevel->upgrade_reward_content,
        'reward_claimed' => false, // Chưa nhận quà
      ]);

      return $history;
    });
  }

  public function claimReward(MembershipUpgradeHistory $history)
  {
    if ($history->reward_claimed) {
      throw new \Exception("Khách hàng đã nhận quà của hạng này rồi!");
    }

    // Đánh dấu đã nhận quà
    $history->update(['reward_claimed' => true]);

    return "Phần quà đã được nhận thành công!";
  }
}
