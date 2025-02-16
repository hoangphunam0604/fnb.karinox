<?php

namespace App\Services\Interfaces;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PointHistory;
use Illuminate\Contracts\Pagination\Paginator;

interface PointServiceInterface
{
  /**
   * Lấy lịch sử điểm của khách hàng (bao gồm tích lũy, sử dụng, khôi phục).
   * @param Customer $customer
   * @param int $perPage Số lượng bản ghi trên mỗi trang (phân trang)
   * @return Paginator
   */
  public function getCustomerPointHistory(Customer $customer, int $perPage = 10): Paginator;

  /**
   * Cập nhật điểm khách hàng (cộng hoặc trừ)
   * @param Customer $customer
   * @param int $loyaltyPoints Điểm tích luỹ
   * @param int $rewardPoints Điểm thưởng
   * @param string $transactionType loại giao dịch tăng, giảm  'earn', 'redeem'
   * @return PointHistory
   */
  public function updatePoints(Customer $customer, int $loyaltyPoints, int $rewardPoints, string $transactionType, array $metadata = []): ?PointHistory;

  /**
   * Cộng điểm cho khách hàng*
   * @param Customer $customer
   * @param int $loyaltyPoints Điểm tích luỹ
   * @param int $rewardPoints Điểm thưởng
   * @return PointHistory
   */
  public function earnPoints(Customer $customer, int $loyaltyPoints, int $rewardPoints,  array $metadata = []): ?PointHistory;

  /**
   * Trừ điểm khách hàng 
   * @param Customer $customer
   * @param int $loyaltyPoints Điểm tích luỹ
   * @param int $rewardPoints Điểm thưởng
   * @return PointHistory
   */
  public function redeemPoints(Customer $customer, int $loyaltyPoints, int $rewardPoints,  array $metadata = []): ?PointHistory;

  /**
   * Sử dụng điểm thưởng
   */
  public function useRewardPoints(Customer $customer, int $rewardPoints,  array $metadata = []): PointHistory;


  /**
   * Sử dụng điểm thưởng để thanh toán đơn hàng.
   * @param Customer $customer
   * @param Order $order
   * @param int $requestedPoints
   * @return bool
   */
  public function useRewardPointsForOrder(Order $order, int $requestedPoints): void;

  /**
   * Khôi phục điểm đã sử dụng khi huỷ đơn đặt hàng.
   * @param Order $order
   * @return void
   */
  public function restorePointsOnOrderCancellation(Order $order): void;

  /**
   * Đặt hàng: Tính giá trị điểm thưởng nhận được từ đơn hàng
   * @param Order $order
   * @return array [$loyaltyPoints, $rewardPoints]
   */
  public function calculatePointsFromOrder(Order $order): array;


  /**
   * Hoá đơn thành công: Chuyển điểm đã sử dụng từ đơn hàng sang hóa đơn tương ứng.
   * @param Invoice $invoice
   * @return void
   */
  public function transferUsedPointsToInvoice(Invoice $invoice): void;

  /**
   * Hoá đơn thành công: Cộng điểm tích lũy và điểm thưởng khi hóa đơn hoàn thành.
   * @param Customer $customer
   * @param Invoice $invoice
   * @return void
   */
  public function addPointsOnInvoiceCompletion(Invoice $invoice): void;


  /**
   * Hoá đơn huỷ bỏ: Khôi phục điểm đã sử dụng, điểm tích lũy và điểm thưởng khi hoá đơn bị huỷ.
   * @param Invoice $invoice
   * @return void
   */
  public function restorePointsOnInvoiceCancellation(Invoice $invoice): void;
}
