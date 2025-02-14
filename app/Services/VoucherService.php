<?php

namespace App\Services;

use App\Models\Customer;
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
   * Lấy danh sách voucher có thể sử dụng
   */
  public function getValidVouchers($customerId = null)
  {
    $now = Carbon::now();
    $dayOfWeek = $now->dayOfWeek;
    $weekOfMonth = ceil($now->day / 7);
    $month = $now->month;
    $time = $now->format('H:i');

    $query = Voucher::where('is_active', true)
      ->where('start_date', '<=', $now)
      ->where('end_date', '>=', $now)
      ->where(function ($q) use ($dayOfWeek) {
        $q->whereNull('valid_days_of_week')
          ->orWhereJsonContains('valid_days_of_week', $dayOfWeek);
      })
      ->where(function ($q) use ($weekOfMonth) {
        $q->whereNull('valid_weeks_of_month')
          ->orWhereJsonContains('valid_weeks_of_month', $weekOfMonth);
      })
      ->where(function ($q) use ($month) {
        $q->whereNull('valid_months')
          ->orWhereJsonContains('valid_months', $month);
      })
      ->where(function ($q) use ($time) {
        $q->whereNull('valid_time_ranges')
          ->orWhereRaw("JSON_CONTAINS(valid_time_ranges, '\"$time\"')");
      })
      ->where(function ($q) use ($now) {
        $q->whereNull('excluded_dates')
          ->orWhereRaw("NOT JSON_CONTAINS(excluded_dates, '\"{$now->toDateString()}\"')");
      })
      ->where(function ($q) {
        $q->whereNull('usage_limit')
          ->orWhereColumn('applied_count', '<', 'usage_limit');
      });

    if ($customerId) {
      $query->where(function ($q) use ($customerId) {
        // Kiểm tra giới hạn số lần sử dụng 
        $q->whereNull('per_customer_limit')
          ->orWhereRaw("(SELECT COUNT(*) FROM voucher_usages WHERE voucher_usages.voucher_id = vouchers.id 
                          AND voucher_usages.customer_id = ?) < vouchers.per_customer_limit", [$customerId]);
      });

      // Truy vấn số danh sách voucher và số lần đã sử dụng trong ngày hôm nay
      $dailyUsageCounts = DB::table('voucher_usages')
        ->selectRaw('voucher_id, COUNT(*) as used_today')
        ->where('customer_id', $customerId)
        ->whereDate('used_at', $now->toDateString())
        ->groupBy('voucher_id')
        ->pluck('used_today', 'voucher_id');

      $query->where(function ($dailyLimitQuery) use ($dailyUsageCounts) {
        $dailyLimitQuery->whereNull('per_customer_daily_limit');

        foreach ($dailyUsageCounts as $voucherId => $usedToday) {
          echo "voucerId: {$voucherId} đã dùng {$usedToday}\n";
          $dailyLimitQuery->orWhere(function ($q) use ($voucherId, $usedToday) {
            $q->where('id', '!=', $voucherId)
              ->whereRaw("? < per_customer_daily_limit", [$usedToday]);
          });
        }
      });

      // Lấy thông tin hạng thành viên của khách hàng
      $customer = Customer::findOrFail($customerId);
      $customerMembershipLevel = $customer->membership_level_id;
      // Kiểm tra hạng thành viên hợp lệ

      $query->where(function ($q) use ($customerMembershipLevel) {
        $q->whereNull('applicable_membership_levels')
          ->orWhereRaw("JSON_CONTAINS(applicable_membership_levels, ?)", [json_encode($customerMembershipLevel)]);
      });
    } else {
      // Nếu không có customerId nhưng voucher có giới hạn số lần sử dụng => Voucher không hợp lệ
      $query->whereNull('per_customer_limit')
        ->whereNull('per_customer_daily_limit')
        ->whereNull('applicable_membership_levels');
    }

    echo $query->toSql();
    // Lấy danh sách voucher hợp lệ
    $validVouchers = $query->get();


    return $validVouchers;
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

    if (!$voucher->is_active || $voucher->start_date > $now || $voucher->end_date < $now) {
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


    if ($customerId) {

      $usedCount = DB::table('voucher_usages')
        ->where('voucher_id', $voucher->id)
        ->where('customer_id', $customerId)
        ->count();

      if ($voucher->per_customer_limit !== null && $usedCount >= $voucher->per_customer_limit) {
        return false;
      }



      $dailyUsedCount = DB::table('voucher_usages')
        ->where('voucher_id', $voucher->id)
        ->where('customer_id', $customerId)
        ->whereDate('used_at', $now->toDateString())
        ->count();

      if ($voucher->per_customer_daily_limit !== null && $dailyUsedCount >= $voucher->per_customer_daily_limit) {
        return false;
      }



      // Kiểm tra hạng thành viên hợp lệ
      if (!empty($voucher->applicable_membership_levels)) {
        $customerService = new CustomerService();
        $customerMembership = $customerService->getCustomerMembershipLevel($customerId);
        $customerMembershipId = $customerMembership->id;

        if (!in_array($customerMembershipId, json_decode($voucher->applicable_membership_levels, true))) {
          return false;
        }
      }
    } else {

      if ($voucher->per_customer_daily_limit !== null || $voucher->per_customer_limit !== null || $voucher->applicable_membership_levels !== null) {
        return false;
      }
    }


    // Kiểm tra ngày trong tuần hợp lệ
    if (!empty($voucher->valid_days_of_week) && !in_array($dayOfWeek, json_decode($voucher->valid_days_of_week, true))) {
      return false;
    }

    // Kiểm tra tuần trong tháng hợp lệ
    if (!empty($voucher->valid_weeks_of_month) && !in_array($weekOfMonth, json_decode($voucher->valid_weeks_of_month, true))) {
      return false;
    }

    // Kiểm tra tháng hợp lệ
    if (!empty($voucher->valid_months) && !in_array($month, json_decode($voucher->valid_months, true))) {
      return false;
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
    if (!empty($voucher->excluded_dates) && in_array($now->toDateString(), json_decode($voucher->excluded_dates, true))) {
      return false;
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
