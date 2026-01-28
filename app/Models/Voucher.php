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
    'code', // Mã voucher duy nhất hiển thị cho khách hàng
    'description', // Mô tả ngắn của voucher
    'voucher_type', // Loại voucher: 'standard' | 'membership'
    'discount_type', // 'fixed' hoặc 'percent'
    'discount_value', // Giá trị giảm (tiền hoặc phần trăm)
    'discount_for_new_product', // Giảm giá đặc biệt cho sản phẩm mới
    'applied_count', // Số lần voucher đã được áp dụng
    'max_discount', // Giảm tối đa (áp dụng cho percent)
    'min_order_value', // Giá trị đơn hàng tối thiểu để áp dụng
    'start_date', // Bắt đầu hiệu lực
    'end_date', // Hết hạn
    'campaign_id', // FK tới voucher_campaigns nếu thuộc chiến dịch
    'customer_id', // Nếu voucher được phát trực tiếp cho khách hàng, lưu customer_id
    'usage_limit', // Tổng số lần voucher có thể được dùng
    'per_customer_limit', // Số lần tối đa một khách hàng có thể dùng
    'per_customer_daily_limit', // Giới hạn dùng theo ngày cho mỗi khách hàng
    'is_active', // Có đang kích hoạt không (on/off)
    'disable_holiday', // Nếu true thì không áp dụng vào ngày lễ
    'applicable_membership_levels', // Mảng id hạng thành viên được áp dụng
    'applicable_arena_member', // Mảng id hội viên arena được áp dụng
    'valid_days_of_week', // Mảng các ngày trong tuần được phép [0-6]
    'valid_weeks_of_month', // Mảng các tuần trong tháng (1-5, 6=tuần cuối)
    'valid_months', // Mảng tháng trong năm [1-12]
    'valid_time_ranges', // Mảng ranges giờ trong ngày (ví dụ 09:00-12:00)
    'excluded_dates', // Ngày cụ thể không áp dụng
  ];

  protected $casts = [
    'applied_count' => 'integer',
    'applicable_membership_levels' => 'array',
    'applicable_arena_member' => 'array',
    'valid_days_of_week' => 'array',
    'valid_weeks_of_month' => 'array',
    'valid_months' => 'array',
    'valid_time_ranges' => 'array',
    'excluded_dates' => 'array',
    'voucher_type' => VoucherType::class,
    'discount_type' => DiscountType::class,
  ];

  public function campaign()
  {
    return $this->belongsTo(VoucherCampaign::class, 'campaign_id');
  }

  public function branches()
  {
    return $this->belongsToMany(Branch::class, 'voucher_branches');
  }

  public function invoices()
  {
    return $this->belongsToMany(Invoice::class, 'invoice_vouchers')
      ->withPivot('invoice_total_before_discount', 'voucher_discount')
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
