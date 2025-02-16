<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PointHistory;
use App\Services\Interfaces\PointHistoryServiceInterface;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

class PointHistoryService implements PointHistoryServiceInterface
{

  /**
   * Lấy lịch sử điểm của khách hàng
   */
  public function getCustomerPointHistory(Customer $customer, int $limit = 10): Paginator
  {
    return PointHistory::where('customer_id', $customer->id)
      ->orderBy('created_at', 'desc')
      ->limit($limit)
      ->get();
  }

  /**
   * Cập nhật điểm khách hàng (cộng hoặc trừ)
   */
  public function updatePoints(Customer $customer, int $loyaltyPoints, int $rewardPoints, string $transactionType, array $metadata = []): PointHistory
  {
    return DB::transaction(function () use ($customer, $loyaltyPoints, $rewardPoints, $transactionType, $metadata) {
      $previousLoyaltyPoints = $customer->loyalty_points;
      $previousRewardPoints = $customer->reward_points;

      $newLoyaltyPoints = max(0, $previousLoyaltyPoints + $loyaltyPoints);
      $newRewardPoints = max(0, $previousRewardPoints + $rewardPoints);

      $customer->update([
        'loyalty_points' => $newLoyaltyPoints,
        'reward_points' => $newRewardPoints
      ]);

      return PointHistory::create(array_merge([
        'customer_id' => $customer->id,
        'transaction_type' => $transactionType,
        'previous_loyalty_points' => $previousLoyaltyPoints,
        'previous_reward_points' => $previousRewardPoints,
        'loyalty_points_changed' => $loyaltyPoints,
        'reward_points_changed' => $rewardPoints,
        'loyalty_points_after' => $newLoyaltyPoints,
        'reward_points_after' => $newRewardPoints,
      ], $metadata));
    });
  }
}
