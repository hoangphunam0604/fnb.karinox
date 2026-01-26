<?php

namespace App\Services;

use App\Contracts\VoucherApplicable;
use App\Enums\DiscountType;
use App\Enums\Msg;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use App\DTO\ValidationResult;
use App\Enums\ProductBookingType;
use App\Enums\VoucherType;
use App\Models\Holiday;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherService
{
  protected CustomerService $customerService;
  protected HolidayService $holidayService;

  public function __construct(CustomerService $customerService, HolidayService $holidayService)
  {
    $this->customerService = $customerService;
    $this->holidayService = $holidayService;
  }

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

  public function getCommonVouchers()
  {
    return Voucher::where('is_active', true)
      ->where('voucher_type', VoucherType::STANDARD)
      ->get();
  }
  /**
   * Lấy danh sách voucher có thể sử dụng
   */
  public function getMemberRewards($customerId = null, $totalPrice = null)
  {
    $now = Carbon::now();
    $dayOfWeek = $now->dayOfWeek;
    $weeksOfMonth = [];
    $weeksOfMonth[] = ceil($now->day / 7);
    if ($this->todayIsLastOccurrenceOfWeekdayInMonth()) {
      $weeksOfMonth[] = 6;
    }
    $now = Carbon::now();


    $month = $now->month;
    $time = $now->format('H:i');
    $dailyUsageCounts = [];
    $query = Voucher::where('is_active', true)
      ->where('voucher_type', VoucherType::MEMBERSHIP)
      ->where(function ($q) use ($now) {
        $q->whereNull('start_date')
          ->orWhere('start_date',  '<=', $now);
      })
      ->where(function ($q) use ($now) {
        $q->whereNull('end_date')
          ->orWhere('end_date',  '>=', $now);
      })


      ->where(function ($q) use ($dayOfWeek) {
        $q->whereNull('valid_days_of_week')
          ->orWhereJsonContains('valid_days_of_week', $dayOfWeek);
      })
      ->where(function ($q) use ($weeksOfMonth) {
        $q->whereNull('valid_weeks_of_month')
          ->orWhere(function ($q2) use ($weeksOfMonth) {
            foreach ($weeksOfMonth as $week) {
              $q2->orWhereRaw("JSON_CONTAINS(valid_weeks_of_month, ?)", [json_encode($week)]);
            }
          });
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
    // Thêm  min_order_value nếu có totalPrice
    if ($totalPrice) {
      $query->where(function ($q) use ($totalPrice) {
        // Kiểm tra giới hạn số lần sử dụng 
        $q->whereNull('min_order_value')
          ->orWhere("min_order_value", '<=', $totalPrice);
      });
    }

    if ($this->holidayService->isHoliday()) {
      $query->where('disable_holiday', false);
    }
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
        ->whereNull('order_extend_id')
        ->whereDate('used_at', $now->toDateString())
        ->groupBy('voucher_id')
        ->pluck('used_today', 'voucher_id');

      $query->where(function ($q) use ($dailyUsageCounts, $customerId) {
        $q->whereNull('per_customer_daily_limit');

        foreach ($dailyUsageCounts as $voucherId => $usedToday) {
          $q->orWhere(function ($subQ) use ($voucherId, $usedToday) {
            $subQ->where('id', $voucherId)
              ->where('per_customer_daily_limit', '>', $usedToday);
          });
        }

        // Bao phủ trường hợp voucher chưa từng được dùng hôm nay
        $q->orWhere(function ($q2) use ($customerId) {
          $q2->whereNotNull('per_customer_daily_limit')
            ->whereNotExists(function ($existsQuery)  use ($customerId) {
              $existsQuery->select(DB::raw(1))
                ->from('voucher_usages')
                ->where('customer_id', $customerId)
                ->whereRaw('voucher_usages.voucher_id = vouchers.id')
                ->whereRaw('DATE(voucher_usages.used_at) = CURDATE()');
            });
        });
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
    //$this->logFullSql($query); // Ghi câu SQL đầy đủ vào log
    // Lấy danh sách voucher hợp lệ
    $validVouchers = $query->get();

    $voucherIds = $validVouchers->pluck('id')->all();

    $totalUsageCounts = DB::table('voucher_usages')
      ->selectRaw('voucher_id, COUNT(*) as used_total')
      ->whereNull('order_extend_id')
      ->whereIn('voucher_id', $voucherIds)
      ->groupBy('voucher_id')
      ->pluck('used_total', 'voucher_id');

    $validVouchers->transform(function ($voucher) use ($dailyUsageCounts, $totalUsageCounts) {
      $usedToday = $dailyUsageCounts[$voucher->id] ?? 0;
      $usedTotal = $totalUsageCounts[$voucher->id] ?? 0;

      $remainingToday = $voucher->per_customer_daily_limit !== null
        ? max(0, $voucher->per_customer_daily_limit - $usedToday)
        : null;

      $remainingTotal = $voucher->per_customer_limit !== null
        ? max(0, $voucher->per_customer_limit - $usedTotal)
        : null;

      // Gộp lại thành 1 field duy nhất
      if (!is_null($remainingToday) && !is_null($remainingTotal)) {
        $voucher->remaining_uses = min($remainingToday, $remainingTotal);
      } elseif (!is_null($remainingToday)) {
        $voucher->remaining_uses = $remainingToday;
      } elseif (!is_null($remainingTotal)) {
        $voucher->remaining_uses = $remainingTotal;
      } else {
        $voucher->remaining_uses = null; // không giới hạn
      }
      $voucher->uses_today = $usedToday;
      $voucher->remaining_uses_today = $remainingToday;

      $voucher->uses_total = $usedToday;
      $voucher->remaining_uses_total = $remainingTotal;
      // Có thể sử dụng không?
      $voucher->is_usable = is_null($voucher->remaining_uses) || $voucher->remaining_uses > 0;

      return $voucher;
    });
    return $validVouchers;
  }

  function logFullSql($query)
  {
    $sql = $query->toSql();
    foreach ($query->getBindings() as $binding) {
      $value = is_numeric($binding) ? $binding : "'" . addslashes($binding) . "'";
      $sql = preg_replace('/\?/', $value, $sql, 1);
    }
    Log::info("Full SQL:\n" . $sql);
  }
  /**
   * Kiểm tra voucher hợp lệ
   */
  /**
   * Kiểm tra voucher có hợp lệ hay không.
   *
   * @param Voucher $voucher
   * @param float $totalPrice
   * @param int|null $customerId
   * @return ValidationResult
   */
  public function isValid(Voucher $voucher, float $totalPrice, ?int $customerId = null): ValidationResult
  {
    $now = Carbon::now();
    $dayOfWeek = $now->dayOfWeek;
    $weeksOfMonth = [];
    $weeksOfMonth[] = ceil($now->day / 7);
    if ($this->todayIsLastOccurrenceOfWeekdayInMonth()) {
      $weeksOfMonth[] = 6;
    }
    $month = $now->month;
    $currentTime = $now->format('H:i');


    // Kiểm tra trạng thái voucher
    if (!$voucher->is_active || ($voucher->start_date && $voucher->start_date > $now) || ($voucher->end_date && $voucher->end_date < $now)) {
      return ValidationResult::fail(config('messages.voucher.inactive_or_expired'));
    }

    // Kiểm tra giá trị tối thiểu của đơn hàng
    if ($voucher->min_order_value && $totalPrice < $voucher->min_order_value) {
      return ValidationResult::fail(config('messages.voucher.min_order_value'));
    }

    // Kiểm tra giới hạn số lần sử dụng
    if ($voucher->usage_limit !== null && $voucher->applied_count >= $voucher->usage_limit) {
      return ValidationResult::fail(config('messages.voucher.usage_limit_exceeded'));
    }
    if ($voucher->disable_holiday && $this->holidayService->isHoliday()) {
      return ValidationResult::fail(config('messages.voucher.disable_holiday'));
    }

    if ($customerId) {
      // Kiểm tra số lần sử dụng của khách hàng
      $usedCount = DB::table('voucher_usages')
        ->where('voucher_id', $voucher->id)
        ->where('customer_id', $customerId)
        ->count();

      if ($voucher->per_customer_limit !== null && $usedCount >= $voucher->per_customer_limit) {
        return ValidationResult::fail(config('messages.voucher.per_customer_limit_exceeded'));
      }

      // Kiểm tra số lần sử dụng trong ngày
      $dailyUsedCount = DB::table('voucher_usages')
        ->where('voucher_id', $voucher->id)
        ->where('customer_id', $customerId)
        ->whereDate('used_at', $now->toDateString())
        ->count();

      if ($voucher->per_customer_daily_limit !== null && $dailyUsedCount >= $voucher->per_customer_daily_limit) {
        return ValidationResult::fail(config('messages.voucher.per_customer_daily_limit_exceeded'));
      }

      // Kiểm tra hạng thành viên hợp lệ
      if (!empty($voucher->applicable_membership_levels)) {
        $customerMembership = $this->customerService->getCustomerMembershipLevel($customerId);
        $customerMembershipId = $customerMembership->id;
        if (!in_array($customerMembershipId, $voucher->applicable_membership_levels)) {
          return ValidationResult::fail(config('messages.voucher.invalid_membership_level'));
        }
      }
    } else {
      if ($voucher->per_customer_daily_limit !== null || $voucher->per_customer_limit !== null || $voucher->applicable_membership_levels !== null) {
        return ValidationResult::fail(config('messages.voucher.customer_required'));
      }
    }

    // Kiểm tra ngày trong tuần hợp lệ
    if (!empty($voucher->valid_days_of_week) && !in_array($dayOfWeek, $voucher->valid_days_of_week)) {
      return ValidationResult::fail(config('messages.voucher.invalid_day_of_week'));
    }

    // Kiểm tra tuần trong tháng hợp lệ
    if (!empty($voucher->valid_weeks_of_month) && !array_intersect($weeksOfMonth, $voucher->valid_weeks_of_month)) {
      return ValidationResult::fail(config('messages.voucher.invalid_week_of_month'));
    }

    // Kiểm tra tháng hợp lệ
    if (!empty($voucher->valid_months) && !in_array($month, $voucher->valid_months)) {
      return ValidationResult::fail(config('messages.voucher.invalid_month'));
    }

    // Kiểm tra khung giờ hợp lệ
    if (!empty($voucher->valid_time_ranges)) {
      $validTimeRanges = $voucher->valid_time_ranges;
      $isValidTime = false;

      foreach ($validTimeRanges as $range) {
        list($startTime, $endTime) = explode('-', $range);
        if ($currentTime >= $startTime && $currentTime <= $endTime) {
          $isValidTime = true;
          break;
        }
      }

      if (!$isValidTime) {
        return ValidationResult::fail(config('messages.voucher.invalid_time_range'));
      }
    }

    // Kiểm tra ngày bị loại trừ
    if (!empty($voucher->excluded_dates) && in_array($now->toDateString(), $voucher->excluded_dates)) {
      return ValidationResult::fail(config('messages.voucher.excluded_date'));
    }

    return ValidationResult::success(config('messages.voucher.valid'));
  }

  /**
   * Áp dụng voucher cho đơn hàng
   * Voucher chỉ được áp dụng cho các order items chưa được giảm giá
   * 
   * @param Order $order
   * @param string $voucherCode
   * @return ValidationResult
   */
  public function applyVoucher(Order $order, string $voucherCode): ValidationResult
  {
    $voucher = Voucher::where('code', $voucherCode)->first();
    if (!$voucher) {
      return ValidationResult::fail(config('messages.voucher.not_found'));
    }
    // Kiểm tra xem voucher đã được sử dụng trên đơn hàng này chưa
    $voucherUsed = VoucherUsage::where(['voucher_id' => $voucher->id, 'order_id' => $order->id])->exists();

    if ($voucherUsed)
      return ValidationResult::fail(config('messages.voucher.used'));

    if (!$order->extend_id): // Bỏ qua kiểm tra voucher nếu là đơn kế thừa
      // Tính tổng giá trị các items hợp lệ
      $eligibleTotal = $this->getEligibleTotal($order);

      // Kiểm tra voucher hợp lệ với tổng giá trị items hợp lệ
      $checkValid = $this->isValid($voucher, $eligibleTotal, $order->customer_id);
      if ($checkValid->success == false) {
        return $checkValid;
      }
    endif;

    return $this->useVoucher($order, $voucher);
  }

  /**
   * Sử dụng voucher - Áp dụng cho từng order item chưa được giảm giá
   * 
   * @param Order $order
   * @param Voucher $voucher
   * @return ValidationResult
   */
  public function useVoucher(Order $order, Voucher $voucher): ValidationResult
  {
    // Lọc ra các items chưa được giảm giá (không có discount_type)
    $eligibleItems = $this->getEligibleitems($order);

    // Nếu không có item nào hợp lệ
    if ($eligibleItems->isEmpty()) {
      return ValidationResult::fail('Không có sản phẩm nào hợp lệ để áp dụng voucher');
    }

    // Tính tổng giá trị các items hợp lệ
    $eligibleTotal = $this->getEligibleTotal($order);

    // Xử lý tùy theo loại voucher
    if ($voucher->discount_type === DiscountType::PERCENT) {
      // Áp dụng % trực tiếp cho mỗi item cho đến khi chạm max_discount
      $totalVoucherDiscount = 0;
      $maxDiscount = $voucher->max_discount;
      $processedNewProducts = []; // Tracking sản phẩm mới đã được giảm giá

      foreach ($eligibleItems as $item) {
        Log::info("SP Mới: {$item->product->is_new} --  {$item->product->name}");

        if ($item->product && $item->product->is_new && $voucher->discount_for_new_product) {
          // Kiểm tra xem sản phẩm này đã được giảm giá chưa
          if (in_array($item->product_id, $processedNewProducts)) {
            // Đã giảm giá rồi, bỏ qua và áp dụng voucher bình thường ở dưới
          } else {
            // Đánh dấu sản phẩm đã được xử lý
            $processedNewProducts[] = $item->product_id;

            // Nếu quantity > 1, tách thành 2 items
            if ($item->quantity > 1) {
              // Tạo item mới cho phần còn lại (không giảm giá đặc biệt)
              $remainingItem = $item->replicate();
              $remainingItem->quantity = $item->quantity - 1;
              $remainingItem->discount_type = DiscountType::PERCENT;
              $remainingItem->discount_percent = $voucher->discount_value;
              $remainingItem->discount_note = $voucher->code;
              $remainingItem->save();

              // Cập nhật item hiện tại về quantity = 1
              $item->quantity = 1;
            }

            // Áp dụng giảm giá đặc biệt cho 1 sản phẩm
            $item->discount_type = DiscountType::PERCENT;
            $item->discount_percent = $voucher->discount_for_new_product;
            $item->discount_note = $voucher->code;
            $item->save(); // Auto calculatePrices() sẽ tính discount_amount

            // Refresh item
            $item->refresh();
            continue; // Chuyển sang item tiếp theo
          }
        }
        // Tính discount cho item này
        $itemDiscount = round(($item->unit_price * $voucher->discount_value / 100) * $item->quantity, 2);

        // Kiểm tra xem có chạm max_discount không
        if ($maxDiscount && ($totalVoucherDiscount + $itemDiscount) > $maxDiscount) {
          // Nếu chạm max, bỏ qua item này để tiếp tục các item tiếp theo, tránh sót sản phẩm mới
          continue;
        }

        // Áp dụng discount cho item
        $item->discount_type = DiscountType::PERCENT;
        $item->discount_percent = $voucher->discount_value;
        $item->discount_note = $voucher->code;
        $item->save(); // Auto calculatePrices() sẽ tính discount_amount

        // Cộng dồn discount
        $item->refresh();
        $totalVoucherDiscount += ($item->discount_amount * $item->quantity);
      }
    } elseif ($voucher->discount_type === DiscountType::FIXED) {
      // Phân bổ số tiền cố định cho các items theo tỷ lệ
      $totalVoucherDiscount = min($voucher->discount_value, $eligibleTotal);
      $remainingDiscount = $totalVoucherDiscount;
      $itemCount = $eligibleItems->count();
      $processedCount = 0;

      foreach ($eligibleItems as $item) {
        $processedCount++;

        // Item cuối cùng nhận phần discount còn lại để tránh sai lệch làm tròn
        if ($processedCount === $itemCount) {
          $itemDiscount = $remainingDiscount;
        } else {
          // Tính discount theo tỷ lệ (cho 1 đơn vị)
          $itemDiscount = round(($item->total_price / $eligibleTotal) * $totalVoucherDiscount / $item->quantity, 2);
          $remainingDiscount -= ($itemDiscount * $item->quantity);
        }

        // Cập nhật item với discount từ voucher
        $item->discount_type = DiscountType::FIXED;
        $item->discount_amount = $itemDiscount;
        $item->discount_note = $voucher->code;
        $item->save(); // Auto calculatePrices() sẽ được gọi
      }
    } else {
      DB::rollBack();
      return ValidationResult::fail('Loại voucher không hợp lệ');
    }

    // Tăng số lần sử dụng voucher
    $voucher->increment('applied_count');

    // Refresh order để tính lại tổng discount thực tế từ các items
    $order->refresh();

    // Tính tổng discount thực tế từ các items đã được áp dụng voucher
    $actualDiscount = 0;
    foreach ($order->items as $item) {
      if ($item->discount_note === $voucher->code) {
        $actualDiscount += ($item->discount_amount * $item->quantity);
      }
    }

    // Lưu thông tin voucher đã sử dụng kèm snapshot
    $voucherSnapshot = [
      'id' => $voucher->id,
      'code' => $voucher->code,
      'description' => $voucher->description,
      'voucher_type' => $voucher->voucher_type,
      'discount_type' => $voucher->discount_type,
      'discount_value' => $voucher->discount_value,
      'max_discount' => $voucher->max_discount,
      'min_order_value' => $voucher->min_order_value,
      'campaign_id' => $voucher->campaign_id,
    ];

    VoucherUsage::create([
      'voucher_id' => $voucher->id,
      'customer_id' => $order->customer_id,
      'order_extend_id' => $order->extend_id,
      'order_id' => $order->id,
      'invoice_total_before_discount' => $eligibleTotal,
      'invoice_total_after_discount' => $eligibleTotal - $actualDiscount,
      'voucher_discount' => $actualDiscount,
      'used_at' => now(),
      'voucher_snapshot' => json_encode($voucherSnapshot),
    ]);

    // Tính lại tổng tiền đơn hàng
    $newTotal = $order->items->sum('total_price');

    $order->update([
      'voucher_id' => $voucher->id,
      'voucher_code' => $voucher->code,
      'voucher_discount' => $actualDiscount,
      'total_price' => $newTotal
    ]);

    return ValidationResult::success(config('messages.voucher.applied_success'));
  }
  /**
   * Hoá đơn thành công: Chuyển voucher đã sử dụng từ đơn hàng sang hóa đơn tương ứng.
   */
  public function transferUsedVoucherToInvoice(int $orderId, int $invoiceId): void
  {
    VoucherUsage::where('order_id', $orderId)->update(['invoice_id' => $invoiceId]);
  }

  /**
   * Hoàn lại voucher khi giao dịch bị hủy
   * Xóa discount từ voucher khỏi các order items
   */
  public function restoreVoucherUsage(VoucherApplicable $transaction): array
  {
    if ($transaction->canNotRestoreVoucher()) {
      return [
        'success' => false,
        'message' => $transaction->getMsgVoucherCanNotRestore()
      ];
    }
    $voucherUsage = VoucherUsage::where((string) $transaction->getSourceIdField(), $transaction->getTransactionId())->first();
    if (!$voucherUsage) {
      return ['success' => false, 'message' => $transaction->getMsgVoucherNotFound()];
    }
    DB::transaction(function () use ($voucherUsage, $transaction) {
      // Hoàn lại số lần sử dụng voucher
      $voucher = Voucher::find($voucherUsage->voucher_id);
      if ($voucher) {
        $voucher->decrement('applied_count');
      }

      // Xóa discount từ voucher khỏi các order items (dựa vào discount_note)
      if ($transaction instanceof Order) {
        $transaction->load('items');
        foreach ($transaction->items as $item) {
          if ($item->discount_note === $voucher->code) {
            $item->discount_type = null;
            $item->discount_amount = 0;
            $item->discount_percent = 0;
            $item->discount_note = null;
            $item->save(); // Auto calculatePrices() sẽ được gọi
          }
        }
      }

      // Xóa bản ghi sử dụng voucher
      $voucherUsage->delete();
      $transaction->removeVoucherUsed();
    });

    return ['success' => true, 'message' => Msg::VOUCHER_RESTORE_SUCCESSFULL];
  }

  /**
   * Tính tổng giá trị các order items hợp lệ để áp dụng voucher
   * (chỉ tính các items chưa được giảm giá)
   * 
   * @param Order $order
   * @return float
   */
  public function getEligibleTotal(Order $order): float
  {
    $eligibleItems = $this->getEligibleitems($order);
    return $eligibleItems->sum('total_price');
  }

  public function getEligibleItems(Order $order)
  {
    $order->load('items.product');

    return $order->items->filter(function ($item) {
      return !$item->isBookingProduct();
    });
  }

  private function todayIsLastOccurrenceOfWeekdayInMonth(?Carbon $date = null): bool
  {
    $date = $date ?? Carbon::now();

    $currentWeekday = $date->dayOfWeek; // 0 = CN, 1 = T2, ..., 6 = T7
    $lastDayOfMonth = $date->copy()->endOfMonth();

    // Tìm thứ giống với hôm nay, bắt đầu từ cuối tháng đi lùi lại
    while ($lastDayOfMonth->dayOfWeek !== $currentWeekday) {
      $lastDayOfMonth->subDay();
    }

    return $date->isSameDay($lastDayOfMonth);
  }
}
