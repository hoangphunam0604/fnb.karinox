<?php

namespace App\Listeners;

use App\Events\InvoiceCompleted;
use App\Services\CustomerService;
use App\Services\PointService;
use App\Services\SystemSettingService;

class InvoicePointsProcess
{
  protected PointService $pointService;
  protected CustomerService $customerService;
  protected SystemSettingService $systemSettingService;

  public function __construct(PointService $pointService, CustomerService $customerService, SystemSettingService $systemSettingService)
  {
    $this->pointService = $pointService;
    $this->customerService = $customerService;
    $this->systemSettingService = $systemSettingService;
  }


  public function handle(InvoiceCompleted $event)
  {
    $invoice = $event->invoice;
    $customer = $invoice->customer;

    if (!$customer) {
      return;
    }

    // Lấy tỷ lệ quy đổi điểm từ SystemSettingService
    $conversionRate = $this->systemSettingService->getPointConversionRate();

    // Nếu tổng tiền = 0 hoặc nhỏ hơn tỷ lệ quy đổi thì không cộng điểm
    if ($invoice->total_amount <= 0 || $invoice->total_amount < $conversionRate) {
      return;
    }

    // Tính toán điểm thưởng
    $pointsEarned = floor($invoice->total_amount / $conversionRate);
    $loyaltyPoints = $pointsEarned;
    $rewardPoints = $this->calculateRewardPoints($customer, $pointsEarned);

    // Cập nhật tổng số tiền đã chi tiêu
    $customer->total_spent += $invoice->total_amount;

    $note = "Cộng điểm từ đơn hàng: {$invoice->code}";
    $this->pointService->addPoints($customer, $loyaltyPoints, $rewardPoints, 'invoice', $invoice->id, $note);

    // Lưu lại thông tin cập nhật
    $customer->save();
    // Cập nhật cấp độ thành viên
    $this->customerService->updateMembershipLevel($customer);
  }

  /**
   * Tính điểm thưởng có hệ số nhân dựa vào ngày sinh nhật.
   * Hệ số nhỏ nhất là 1
   */
  private function calculateRewardPoints($customer, $pointsEarned): int
  {
    $multiplier = max(1, ($customer->isBirthdayToday() && $customer->membershipLevel)
      ? $customer->membershipLevel->reward_multiplier ?? 1
      : 1);

    return $pointsEarned * $multiplier;
  }
}
