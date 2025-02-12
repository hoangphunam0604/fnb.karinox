<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VoucherService
{
  /**
   * Thêm voucher mới
   */
  public function create(array $data): Voucher
  {
    return Voucher::create($data);
  }

  /**
   * Sửa voucher
   */
  public function update(Voucher $voucher, array $data): Voucher
  {
    $voucher->update($data);
    return $voucher;
  }

  /**
   * Xóa voucher
   */
  public function delete(Voucher $voucher): bool
  {
    return $voucher->delete();
  }

  /**
   * Tìm voucher theo mã
   */
  public function findByCode(string $code): ?Voucher
  {
    return Voucher::where('code', $code)->first();
  }

  /**
   * Lấy danh sách voucher (phân trang)
   */
  public function getAllPaginated($perPage = 10)
  {
    return Voucher::paginate($perPage);
  }

  /**
   * Lấy danh sách voucher có thể sử dụng theo ngày/giờ hiện tại
   */
  public function getValidVouchers()
  {
    $now = Carbon::now();
    $dayOfWeek = $now->dayOfWeek;
    $weekOfMonth = ceil($now->day / 7);
    $month = $now->month;
    $time = $now->format('H:i');

    return Voucher::where('is_active', true)
      ->where('start_date', '<=', $now)
      ->where('end_date', '>=', $now)
      ->where(function ($query) use ($dayOfWeek) {
        $query->whereNull('valid_days_of_week')
          ->orWhereJsonContains('valid_days_of_week', $dayOfWeek);
      })
      ->where(function ($query) use ($weekOfMonth) {
        $query->whereNull('valid_weeks_of_month')
          ->orWhereJsonContains('valid_weeks_of_month', $weekOfMonth);
      })
      ->where(function ($query) use ($month) {
        $query->whereNull('valid_months')
          ->orWhereJsonContains('valid_months', $month);
      })
      ->where(function ($query) use ($time) {
        $query->whereNull('valid_time_ranges')
          ->orWhereRaw("JSON_CONTAINS(valid_time_ranges, '\"$time\"')");
      })
      ->where(function ($query) use ($now) {
        $query->whereNull('excluded_dates')
          ->orWhereRaw("NOT JSON_CONTAINS(excluded_dates, '\"{$now->toDateString()}\"')");
      })
      ->get();
  }

  /**
   * Kiểm tra voucher hợp lệ
   */
  public function isValid(Voucher $voucher, $totalOrder, $customerId = null): bool
  {
    $now = Carbon::now();
    $dayOfWeek = $now->dayOfWeek; // 0 = Chủ Nhật, 6 = Thứ Bảy
    $weekOfMonth = ceil($now->day / 7);
    $month = $now->month;
    $currentTime = $now->format('H:i');

    // Kiểm tra trạng thái hoạt động
    if (!$voucher->is_active) {
      return false;
    }

    // Kiểm tra thời gian hiệu lực
    if ($voucher->start_date > $now || $voucher->end_date < $now) {
      return false;
    }

    // Kiểm tra giá trị tối thiểu của order
    if ($voucher->min_order_value && $totalOrder < $voucher->min_order_value) {
      return false;
    }

    // Kiểm tra giới hạn số lần sử dụng
    if ($voucher->usage_limit !== null && $voucher->applied_count >= $voucher->usage_limit) {
      return false;
    }

    // Kiểm tra giới hạn số lần sử dụng theo khách hàng
    if ($customerId && $voucher->per_customer_limit !== null) {
      $usedCount = DB::table('voucher_usages')
        ->where('voucher_id', $voucher->id)
        ->where('customer_id', $customerId)
        ->count();

      if ($usedCount >= $voucher->per_customer_limit)
        return false;
    }

    // Kiểm tra hạng thành viên hợp lệ
    if (!empty($voucher->applicable_membership_levels)) {
      if (!$customerId)
        return false;

      $customerService = new CustomerService();
      $customerMembership = $customerService->getCustomerMembershipLevel($customerId); // Giả sử có hàm lấy hạng thành viên
      if (!in_array($customerMembership, json_decode($voucher->applicable_membership_levels, true))) {
        return false;
      }
    }

    // Kiểm tra ngày trong tuần hợp lệ
    if (!empty($voucher->valid_days_of_week)) {
      $validDays = json_decode($voucher->valid_days_of_week, true);
      if (!in_array($dayOfWeek, $validDays)) {
        return false;
      }
    }

    // Kiểm tra tuần trong tháng hợp lệ
    if (!empty($voucher->valid_weeks_of_month)) {
      $validWeeks = json_decode($voucher->valid_weeks_of_month, true);
      if (!in_array($weekOfMonth, $validWeeks)) {
        return false;
      }
    }

    // Kiểm tra tháng hợp lệ
    if (!empty($voucher->valid_months)) {
      $validMonths = json_decode($voucher->valid_months, true);
      if (!in_array($month, $validMonths)) {
        return false;
      }
    }

    // Kiểm tra khung giờ hợp lệ
    if (!empty($voucher->valid_time_ranges)) {
      $validTimeRanges = json_decode($voucher->valid_time_ranges, true);
      $isValidTime = false;

      foreach ($validTimeRanges as $range) {
        list($startTime, $endTime) = explode('-', $range);
        if ($currentTime >= $startTime && $currentTime <= $endTime) {
          $isValidTime = true;
          break;
        }
      }

      if (!$isValidTime) {
        return false;
      }
    }

    // Kiểm tra ngày bị loại trừ
    if (!empty($voucher->excluded_dates)) {
      $excludedDates = json_decode($voucher->excluded_dates, true);
      if (in_array($now->toDateString(), $excludedDates)) {
        return false;
      }
    }

    return true;
  }


  /**
   * Sử dụng voucher
   */
  public function applyVoucher(Voucher $voucher, $order)
  {
    if (!$this->isValid($voucher, $order->total_price, $order->customer_id)) {
      return ['success' => false, 'message' => 'Voucher không hợp lệ.'];
    }

    // Kiểm tra xem voucher đã được sử dụng trên đơn hàng này chưa
    $voucherUsed = VoucherUsage::where([
      'voucher_id' => $voucher->id,
      'order_id' => $order->id,
    ])->exists();

    if ($voucherUsed) {
      return ['success' => false, 'message' => 'Voucher đã được sử dụng trên đơn hàng này.'];
    }

    // Tính toán giảm giá
    $totalBeforeDiscount = $order->total_price;
    $discount = 0;

    if ($voucher->discount_type === 'fixed') {
      $discount = min($voucher->discount_value, $totalBeforeDiscount);
    } elseif ($voucher->discount_type === 'percentage') {
      $discount = min($totalBeforeDiscount * ($voucher->discount_value / 100), $voucher->max_discount ?? $totalBeforeDiscount);
    }

    // Cập nhật số lần sử dụng voucher
    DB::transaction(function () use ($voucher, $order, $totalBeforeDiscount, $discount) {
      $voucher->increment('applied_count');

      VoucherUsage::create([
        'voucher_id' => $voucher->id,
        'customer_id' => $order->customer_id,
        'order_id' => $order->id,
        'invoice_total_before_discount' => $totalBeforeDiscount,
        'invoice_total_after_discount' => $totalBeforeDiscount - $discount,
        'discount_amount' => $discount,
        'used_at' => now(),
      ]);
    });

    return [
      'success' => true,
      'discount' => $discount,
      'final_total' => $totalBeforeDiscount - $discount,
    ];
  }

  /**
   * Hoàn lại voucher khi đơn hàng bị hủy
   */
  public function refundVoucher($order)
  {
    if ($order->order_status === 'completed')
      return ['success' => false, 'message' => 'Không thể hoàn lại voucher vì đơn hàng đã hoàn tất.'];

    $voucherUsage = VoucherUsage::where('order_id', $order->id)->first();

    if (!$voucherUsage) {
      return ['success' => false, 'message' => 'Không tìm thấy voucher để hoàn lại.'];
    }

    DB::transaction(function () use ($voucherUsage) {
      // Hoàn lại số lần sử dụng voucher
      $voucher = Voucher::find($voucherUsage->voucher_id);
      if ($voucher) {
        $voucher->decrement('applied_count');
      }

      // Xóa bản ghi sử dụng voucher
      $voucherUsage->delete();
    });

    return ['success' => true, 'message' => 'Voucher đã được hoàn lại.'];
  }
}
