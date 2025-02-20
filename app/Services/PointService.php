<?php

namespace App\Services;

use App\Contracts\PointEarningTransaction;
use App\Contracts\RewardPointUsable;
use App\Enums\PointHistoryNote;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use App\Models\PointHistory;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\Interfaces\PointServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class PointService implements PointServiceInterface
{
  protected OrderService $orderService;
  protected SystemSettingService $systemSettingService;

  public function __construct(OrderService $orderService, SystemSettingService $systemSettingService)
  {
    $this->orderService = $orderService;
    $this->systemSettingService = $systemSettingService;
  }

  /**
   * Lấy lịch sử điểm của khách hàng
   */
  public function getCustomerPointHistory(Customer $customer, int $limit = 10): LengthAwarePaginator
  {
    return PointHistory::where('customer_id', $customer->id)
      ->orderBy('created_at', 'desc')
      ->paginate($limit);
  }

  /**
   * Cập nhật điểm khách hàng (cộng hoặc trừ)
   */
  public function updatePoints(Customer $customer, int $loyaltyPoints, int $rewardPoints, string $transactionType, array $metadata = []): ?PointHistory
  {
    if ($loyaltyPoints === 0 &&  $rewardPoints === 0) {
      return null;
    }
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

  /**
   * Cộng điểm cho khách hàng
   */
  public function earnPoints(Customer $customer, int $loyaltyPoints, int $rewardPoints, array $metadata = []): PointHistory
  {
    return $this->updatePoints($customer, $loyaltyPoints, $rewardPoints, 'earn', $metadata);
  }

  /**
   * Trừ điểm của khách hàng
   */
  public function redeemPoints(Customer $customer, int $loyaltyPoints, int $rewardPoints, array $metadata = []): PointHistory
  {
    return $this->updatePoints($customer, -$loyaltyPoints, -$rewardPoints, 'redeem', $metadata);
  }

  /**
   * Hoá đơn thành công: Chuyển điểm đã sử dụng từ đơn hàng sang hóa đơn tương ứng.
   */
  public function transferUsedPointsToInvoice(Invoice $invoice): void
  {
    $pointHistory = PointHistory::where('source_type', 'order')
      ->where('source_id', $invoice->order_id)
      ->where('usage_type', 'discount')
      ->where('usage_id', $invoice->order_id)->first();
    if ($pointHistory) {
      $pointHistory->update([
        'source_type' => 'invoice',
        'source_id' => $invoice->id,
        'usage_id' => $invoice->id,
      ]);
    }
  }

  /**
   * Tính giá trị điểm thưởng nhận được từ giao dịch
   */
  public function calculatePointsFromTransaction(PointEarningTransaction $transaction): array
  {
    if (!$transaction->canEarnPoints())
      return [0, 0];

    // Lấy tỷ lệ quy đổi điểm từ SystemSettingService
    $conversionRate = $this->systemSettingService->getPointConversionRate();
    $totalAmount = $transaction->getTotalAmount();
    // Nếu tổng tiền = 0 hoặc nhỏ hơn tỷ lệ quy đổi thì không cộng điểm
    if ($totalAmount <= 0 || $totalAmount < $conversionRate) {
      return [0, 0];
    }
    $customer = $transaction->getCustomer();
    // Tính toán điểm thưởng
    $pointsEarned = floor($totalAmount / $conversionRate);
    $loyaltyPoints = $pointsEarned;
    $rewardPoints = $this->calculateRewardPoints($customer, $pointsEarned);
    return [$loyaltyPoints, $rewardPoints];
  }

  /**
   * Cộng điểm tích lũy và điểm thưởng khi giao dịch hoàn thành
   */
  public function earnPointsOnTransactionCompletion(PointEarningTransaction $transaction): void
  {
    if (!$transaction->canEarnPoints()) {
      return;
    }
    $customer = $transaction->getCustomer();
    [$loyaltyPoints, $rewardPoints] = $this->calculatePointsFromTransaction($transaction);
    $transaction->updatePoints($loyaltyPoints, $rewardPoints);
    $metadata = [
      'source_type' => $transaction->getTransactionType(),
      'source_id' => $transaction->getTransactionId(),
      'note'  =>  $transaction->getEarnedPointsNote()
    ];
    $this->earnPoints($customer, $loyaltyPoints, $rewardPoints, $metadata);
  }


  /**
   * Sử dụng điểm thưởng
   */
  public function useRewardPoints(RewardPointUsable $transaction, int $requestedPoints): void
  {
    if (!$transaction->getCustomer() || $requestedPoints <= 0) {
      return;
    }
    DB::transaction(function () use ($transaction, $requestedPoints) {
      $customer = $transaction->getCustomer();
      $this->validateRewardPointsUsageToOrder($transaction, $requestedPoints);

      [$usedRewardPoints, $rewardDiscount] = $this->calculateRewardPointsCanbeUsedValue($customer, $requestedPoints);

      if ($usedRewardPoints == 0) {
        return;
      }
      $transaction->applyRewardPointsDiscount($usedRewardPoints, $rewardDiscount);

      return $this->redeemPoints($customer, 0, $usedRewardPoints, [
        'source_type' => $transaction->getTransactionType(),
        'source_id' => $transaction->getTransactionId(),
        'usage_type' => 'discount',
        'usage_id' => $transaction->getTransactionId(),
        'note'  =>  PointHistoryNote::ORDER_USER_REWARD_POINTS
      ]);
    });
  }

  /**
   * Khôi phục điểm đã sử dụng trong giao dịch khi huỷ
   */
  public function restoreTransactionRewardPoints(RewardPointUsable $transaction): void
  {
    DB::transaction(function () use ($transaction) {
      $customer = $transaction->getCustomer();
      if (!$customer) return;

      $rewardPointsUsed = $transaction->getRewardPointsUsed();
      if (!$rewardPointsUsed) return;
      $transaction->remoreRewardPointsUsed();

      $this->earnPoints($customer, 0, $rewardPointsUsed, [
        'source_type' => $transaction->getTransactionType(),
        'source_id' => $transaction->getTransactionId(),
        'note'  =>  $transaction->getNoteToRestoreRewardPoints()
      ]);
    });
  }

  /**
   * Khôi phục điểm đã được tăng trong giao dịch (điểm tích luỹ, điểm thưởng)
   */
  public function restoreTransactionEarnedPoints(PointEarningTransaction $transaction): void
  {
    DB::transaction(function () use ($transaction) {
      $customer = $transaction->getCustomer();
      if (!$customer) return;
      $earnedLoyaltyPoints = $transaction->getEarnedLoyaltyPoints();
      $earnedRewardPoints = $transaction->getEarnedRewardPoints();

      if ($earnedLoyaltyPoints <= 0 && $earnedRewardPoints <= 0) {
        return;
      }

      $transaction->removePoints();

      $metadata = [
        'source_id' => $transaction->getTransactionId(),
        'source_type' => $transaction->getTransactionType(),
        'note'  =>  $transaction->getRestoredPointsNote()
      ];
      return $this->redeemPoints($customer, $earnedLoyaltyPoints, $earnedRewardPoints, $metadata);
    });
  }

  /* ============================ */

  /**
   * Tính điểm thưởng có hệ số nhân dựa vào ngày sinh nhật.
   * Hệ số nhỏ nhất là 1
   */
  private function calculateRewardPoints(Customer $customer, int $pointsEarned): int
  {
    $multiplier = max(1, ($customer->isBirthdayToday() && $customer->membershipLevel)
      ? $customer->membershipLevel->reward_multiplier ?? 1
      : 1);
    return $pointsEarned * $multiplier;
  }


  /**
   * Kiểm tra đơn hàng có khách hàng và điểm thưởng phù hợp với đơn hàng.
   */
  private function validateRewardPointsUsageToOrder(RewardPointUsable $transaction, int $requestedPoints)
  {
    if ($requestedPoints <= 0) {
      throw new \InvalidArgumentException('Số điểm sử dụng phải lớn hơn 0.');
    }

    $customer = $transaction->getCustomer();
    if (!$customer) {
      throw new InvalidArgumentException('Đơn hàng chưa có khách hàng.');
    }
    if ($customer->reward_points <= 0) {
      throw new InvalidArgumentException('Khách hàng không có điểm thưởng.');
    }

    if ($requestedPoints > $customer->reward_points) {
      throw new InvalidArgumentException('Số điểm sử dụng vượt quá số điểm hiện có.');
    }

    $conversionRate = $this->systemSettingService->getRewardPointConversionRate();
    $totalAmount = $transaction->getTotalAmount();

    if ($requestedPoints * $conversionRate > $totalAmount) {
      throw new InvalidArgumentException('Điểm thưởng sử dụng không thể vượt quá tổng giá trị đơn hàng.');
    }
  }

  /**
   * Thanh toán bằng điểm: Tính giá trị điểm thưởng có thể sử dụng và số tiền quy đổi
   */
  private function calculateRewardPointsCanbeUsedValue(Customer $customer, int $requestedPoints): array
  {

    $maxUsablePoints = min($requestedPoints, $customer->reward_points);
    if ($maxUsablePoints <= 0) {
      return [0, 0]; // Không có điểm nào có thể sử dụng
    }

    $rewardPointConversionRate = $this->systemSettingService->getRewardPointConversionRate();

    if (!$rewardPointConversionRate || $rewardPointConversionRate <= 0) {
      return [0, 0]; // Tránh lỗi nếu hệ thống chưa thiết lập tỷ lệ quy đổi
    }

    $rewardDiscount = $maxUsablePoints * $rewardPointConversionRate;

    return [$maxUsablePoints, $rewardDiscount];
  }
}
