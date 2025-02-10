<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Voucher extends Model
{
  use HasFactory;

  protected $fillable = [
    'code',
    'discount_type',
    'discount_value',
    'min_order_value',
    'max_discount',
    'usage_limit',
    'used_count',
    'expires_at',
    'status',
  ];

  protected $casts = [
    'discount_value' => 'decimal:2',
    'min_order_value' => 'decimal:2',
    'max_discount' => 'decimal:2',
    'usage_limit' => 'integer',
    'used_count' => 'integer',
    'expires_at' => 'datetime',
  ];

  /**
   * Tự động tạo mã giảm giá nếu không có.
   */
  protected static function boot()
  {
    parent::boot();

    static::creating(function ($voucher) {
      if (empty($voucher->code)) {
        $voucher->code = strtoupper(Str::random(8));
      }
    });
  }
  /**
   * Scope tìm kiếm voucher theo mã code.
   */
  public function scopeFindByCode($query, $code)
  {
    return $query->where('code', strtoupper($code))->first();
  }

  /**
   * Kiểm tra voucher còn hiệu lực hay không.
   */
  public function isValid()
  {
    return $this->status === 'active'
      && ($this->expires_at === null || $this->expires_at->isFuture())
      && ($this->usage_limit === null || $this->used_count < $this->usage_limit);
  }

  /**
   * Tăng số lần sử dụng voucher.
   */
  public function incrementUsage()
  {
    $this->increment('used_count');
  }
}
