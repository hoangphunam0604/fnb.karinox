<?php

namespace App\Services;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use App\Models\PointHistory;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use InvalidArgumentException;

class PointService
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
  public function getCustomerPointHistory(Customer $customer, int $limit = 10): Paginator
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
   * Sử dụng điểm thưởng
   */
  public function useRewardPoints(Customer $customer, int $rewardPoints,  array $metadata = []): PointHistory
  {
    return $this->redeemPoints($customer, 0, $rewardPoints, $metadata);
  }

  /**
   * Đặt hàng: Sử dụng điểm thưởng để thanh toán đơn hàng.
   */
  public function useRewardPointsForOrder(Order $order, int $requestedPoints): void
  {
    if (!$order->customer || $requestedPoints <= 0) {
      return;
    }
    DB::transaction(function () use ($order, $requestedPoints) {
      $customer = $order->customer;
      $this->validateRewardPointsUsageToOrder($order, $requestedPoints);

      [$usedRewardPoints, $rewardDiscount] = $this->calculateRewardPointsCanbeUsedValue($customer, $requestedPoints);

      if ($usedRewardPoints == 0) {
        return;
      }
      $order->update([
        'reward_points_used' => $usedRewardPoints,
        'reward_discount' => $rewardDiscount
      ]);
      $this->orderService->updateTotalPrice($order);

      $this->useRewardPoints(
        $customer,
        $usedRewardPoints,
        [
          'source_type' => 'order',
          'source_id' => $order->id,
          'usage_type' => 'discount',
          'usage_id' => $order->id,
          'note'  =>  "Sử dụng điểm thưởng để thanh toán"
        ]
      );
    });
  }

  /**
   * Huỷ đặt hàng: Khôi phục điểm đã sử dụng khi huỷ đơn đặt hàng.
   */
  public function restoreRewardPointsUsedOnOrderCancellation(Order $order): void
  {
    $customer = $order->customer;
    if (!$customer) {
      return;
    }
    $rewardPointsUsed = $order->reward_points_used;
    if ($rewardPointsUsed > 0) {
      DB::transaction(function () use ($order, $customer,  $rewardPointsUsed) {
        $this->earnPoints($customer, 0, $rewardPointsUsed, [
          'source_type' => 'order',
          'source_id' => $order->id,
          'note'  =>  "Hoàn điểm đã dùng khi hủy đặt hàng"
        ]);
      });
    }
  }

  /**
   * Đặt hàng: Tính giá trị điểm thưởng nhận được hóa đơn
   */
  public function calculatePointsFromInvoice(Invoice $invoice): array
  {
    if (!$invoice->customer)
      return [0, 0];

    // Lấy tỷ lệ quy đổi điểm từ SystemSettingService
    $conversionRate = $this->systemSettingService->getPointConversionRate();

    // Nếu tổng tiền = 0 hoặc nhỏ hơn tỷ lệ quy đổi thì không cộng điểm
    if ($invoice->total_amount <= 0 || $invoice->total_amount < $conversionRate) {
      return [0, 0];
    }
    // Tính toán điểm thưởng
    $pointsEarned = floor($invoice->total_amount / $conversionRate);
    $loyaltyPoints = $pointsEarned;
    $rewardPoints = $this->calculateRewardPoints($invoice->customer, $pointsEarned);
    return [$loyaltyPoints, $rewardPoints];
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
   * Hoá đơn thành công: Cộng điểm tích lũy và điểm thưởng khi hóa đơn hoàn thành.
   */
  public function addPointsOnInvoiceCompletion(Invoice $invoice): void
  {
    if (!$invoice->customer) {
      return;
    }
    if ($invoice->earned_loyalty_points <= 0 && $invoice->earned_reward_points <= 0) {
      return;
    }
    $customer = $invoice->customer;
    $loyaltyPoints = $invoice->earned_loyalty_points;
    $rewardPoints = $invoice->earned_reward_points;
    $metadata = [
      'source_type' => 'invoice',
      'source_id' => $invoice->id,
    ];
    $this->earnPoints($customer, $loyaltyPoints, $rewardPoints, $metadata);
  }


  /**
   * Hoá đơn huỷ bỏ: Khôi phục điểm đã sử dụng, điểm tích lũy và điểm thưởng khi hoá đơn bị huỷ.
   */
  public function restorePointsOnInvoiceCancellation(Invoice $invoice): void
  {
    if (!$invoice->customer) {
      return;
    }
    if ($invoice->earned_loyalty_points <= 0 && $invoice->earned_reward_points <= 0 && $invoice->reward_points_used <= 0) {
      return; // Không có gì để khôi phục, thoát sớm
    }
    DB::transaction(function () use ($invoice) {
      $this->restoreRewardPointsUsedOnInvoiceCancellation($invoice);
      $this->restoreRewardPointsUsedOnInvoiceCancellation($invoice);
    });
  }

  /**
   * Trừ điểm tích luỹ, điểm thưởng được cộng từ hoá đơn đã huỷ
   */
  private function restoreLoyaltyAndRewardPointsOnInvoiceCancellation(Invoice $invoice)
  {
    $customer = $invoice->customer;
    $loyaltyPoints = $invoice->earned_loyalty_points;
    $rewardPoints = $invoice->earned_reward_points;

    if ($loyaltyPoints <= 0 && $rewardPoints <= 0) {
      return;
    }
    $metadata = [
      'source_type' => 'invoice',
      'source_id' => $invoice->id,
      'note'  =>  "Hoàn điểm tích luỹ và điểm thưởng từ đơn hàng bị huỷ"
    ];
    return $this->redeemPoints($customer, $loyaltyPoints, $rewardPoints, $metadata);
  }

  /**
   * Cộng lại điểm thưởng đã sử dụng để thanh toán đơn hàng
   */
  private function restoreRewardPointsUsedOnInvoiceCancellation(Invoice $invoice)
  {
    $customer = $invoice->customer;
    $usedRewardPoints = $invoice->reward_points_used;

    if ($usedRewardPoints <= 0) {
      return;
    }
    $metadata = [
      'source_type' => 'invoice',
      'source_id' => $invoice->id,
      'note'  =>  "Cộng lại điểm thưởng đã sử dụng từ đơn hàng bị huỷ"
    ];
    return $this->earnPoints($customer, 0, $usedRewardPoints, $metadata);
  }


  /**
   * Hoá đơn thành công: Tính điểm thưởng có hệ số nhân dựa vào ngày sinh nhật.
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
  private function validateRewardPointsUsageToOrder(Order $order, int $requestedPoints)
  {
    if ($requestedPoints <= 0) {
      throw new \InvalidArgumentException('Số điểm sử dụng phải lớn hơn 0.');
    }

    if (!$order->customer) {
      throw new InvalidArgumentException('Đơn hàng chưa có khách hàng.');
    }
    $customer = $order->customer;
    if ($customer->reward_points <= 0) {
      throw new InvalidArgumentException('Khách hàng không có điểm thưởng.');
    }

    if ($requestedPoints > $customer->reward_points) {
      throw new InvalidArgumentException('Số điểm sử dụng vượt quá số điểm hiện có.');
    }

    $conversionRate = $this->systemSettingService->getRewardPointConversionRate();
    $totalAmount = $order->total_price;

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
