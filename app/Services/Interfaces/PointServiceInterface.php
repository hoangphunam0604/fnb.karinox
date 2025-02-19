<?php

namespace App\Services\Interfaces;

use App\Contracts\PointEarningTransaction;
use App\Contracts\RewardPointUsable;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PointHistory;

interface PointServiceInterface
{
  /**
   * Lấy lịch sử điểm của khách hàng
   */
  public function getCustomerPointHistory(Customer $customer, int $limit = 10): LengthAwarePaginator;

  /**
   * Cập nhật điểm khách hàng (cộng hoặc trừ)
   */
  public function updatePoints(Customer $customer, int $loyaltyPoints, int $rewardPoints, string $transactionType, array $metadata = []): ?PointHistory;

  /**
   * Cộng điểm cho khách hàng
   */
  public function earnPoints(Customer $customer, int $loyaltyPoints, int $rewardPoints, array $metadata = []): PointHistory;

  /**
   * Trừ điểm của khách hàng
   */
  public function redeemPoints(Customer $customer, int $loyaltyPoints, int $rewardPoints, array $metadata = []): PointHistory;

  /**
   * Hoá đơn thành công: Chuyển điểm đã sử dụng từ đơn hàng sang hóa đơn tương ứng
   */
  public function transferUsedPointsToInvoice(Invoice $invoice): void;

  /**
   * Tính giá trị điểm thưởng nhận được từ một giao dịch
   */
  public function calculatePointsFromTransaction(PointEarningTransaction $transaction): array;

  /**
   * Cộng điểm tích lũy và điểm thưởng khi giao dịch hoàn thành
   */
  public function earnPointsOnTransactionCompletion(PointEarningTransaction $transaction): void;

  /**
   * Khôi phục điểm đã sử dụng trong giao dịch khi huỷ
   * restoreRewardPointsOnTransactionCancellation
   */
  public function restoreTransactionRewardPoints(RewardPointUsable $transaction): void;

  /**
   * Khôi phục điểm đã được tăng trong giao dịch (điểm tích luỹ, điểm thưởng)
   */
  public function restoreTransactionEarnedPoints(PointEarningTransaction $transaction): void;


  /**
   * Sử dụng điểm thưởng
   */
  public function useRewardPoints(RewardPointUsable $transaction, int $rewardPoints): void;
}
