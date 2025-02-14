<?php

namespace App\Listeners;

use App\Events\InvoiceCompleted;
use App\Models\Customer;
use App\Models\SystemSetting;
use App\Services\PointService;
use Carbon\Carbon;

class ProcessInvoicePoints
{
  protected PointService $pointService;

  public function __construct(PointService $pointService)
  {
    $this->pointService = $pointService;
  }

  public function handle(InvoiceCompleted $event)
  {
    $invoice = $event->invoice;
    $customer = $invoice->customer;

    if (!$customer) {
      return;
    }

    // Lấy tỷ lệ quy đổi điểm từ database
    $conversionRate = SystemSetting::where('key', 'point_conversion_rate')->value('value');

    // Nếu không tìm thấy, dùng mặc định 25,000 VNĐ = 1 điểm
    $conversionRate = $conversionRate ? floatval($conversionRate) : 25000;

    $pointsEarned = floor($invoice->total_amount / $conversionRate);

    // Cộng điểm tích lũy (loyalty_points)
    $loyaltyPoints = $pointsEarned;

    // Mặc định không có hệ số nhân
    $multiplier = 1;

    // Kiểm tra nếu là ngày sinh nhật và có hệ số nhân từ membership_levels
    if ($customer->isEligibleForBirthdayBonus() && $customer->membershipLevel) {
      $multiplier = $customer->membershipLevel->reward_multiplier ?? 1;
      $customer->last_birthday_bonus_date = Carbon::now();
    }

    // Cộng điểm thưởng (reward_points) với hệ số nhân
    $rewardPoints = $pointsEarned * $multiplier;

    // Cập nhật tổng số tiền đã chi tiêu
    $customer->total_spent += $invoice->total_amount;
    $this->pointService->addPoints($customer, $loyaltyPoints, $rewardPoints, 'invoice', $invoice->id);

    // Lưu lại thông tin cập nhật
    $customer->save();

    // Cập nhật cấp độ thành viên
    $customer->updateMembershipLevel();

    $conversionRate = config('loyalty.point_conversion_rate', 25000);
    $loyaltyPoints = floor($invoice->total_amount / $conversionRate);
    $rewardPoints = floor($loyaltyPoints * 0.5); // Ví dụ: 50% điểm tích lũy được chuyển thành điểm thưởng

  }
}
