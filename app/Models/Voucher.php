<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\VoucherType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Voucher extends Model
{
  use HasFactory;

  protected $fillable = [
    'code',
    'description',
    'voucher_type',
    'discount_type',
    'discount_value',
    'applied_count',
    'max_discount',
    'min_order_value',
    'start_date',
    'end_date',
    'usage_limit',
    'per_customer_limit',
    'per_customer_daily_limit',
    'is_active',
    'disable_holiday',
    'applicable_membership_levels',
    'valid_days_of_week',
    'valid_weeks_of_month',
    'valid_months',
    'valid_time_ranges',
    'excluded_dates',
    'warn_if_used',
  ];

  protected $casts = [
    'applied_count' => 'integer',
    'applicable_membership_levels' => 'array',
    'valid_days_of_week' => 'array',
    'valid_weeks_of_month' => 'array',
    'valid_months' => 'array',
    'valid_time_ranges' => 'array',
    'excluded_dates' => 'array',
    'voucher_type' => VoucherType::class,
    'discount_type' => DiscountType::class,
  ];

  public function branches()
  {
    return $this->belongsToMany(Branch::class, 'voucher_branches');
  }

  public function invoices()
  {
    return $this->belongsToMany(Invoice::class, 'invoice_vouchers')
      ->withPivot('invoice_total_before_discount', 'discount_amount')
      ->withTimestamps();
  }

  public function restoreUsage($customerId = null)
  {
    // Nếu voucher có giới hạn tổng số lần sử dụng, tăng lại 1 lần
    if ($this->usage_limit !== null) {
      $this->increment('usage_limit');
    }

    // Nếu voucher có giới hạn số lần sử dụng trên mỗi khách hàng, tăng lại cho khách hàng đó
    if ($this->per_customer_limit !== null && $customerId) {
      DB::table('voucher_usages')->where([
        'voucher_id' => $this->id,
        'customer_id' => $customerId,
      ])->increment('usage_count', 1);
    }
  }
}
