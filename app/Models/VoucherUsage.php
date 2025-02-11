<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherUsage extends Model
{
  use HasFactory;

  protected $fillable = [
    'voucher_id',
    'order_id',
    'customer_id',
    'invoice_id',
    'used_at',
    'discount_amount',
    'invoice_total_before_discount',
    'invoice_total_after_discount',
  ];

  public $timestamps = false; // Không cần timestamps vì đã có `used_at`

  protected $primaryKey = ['voucher_id', 'order_id']; // Định nghĩa khóa chính phức hợp
  public $incrementing = false; // Không có cột ID tự động tăng

  public function voucher()
  {
    return $this->belongsTo(Voucher::class);
  }

  public function customer()
  {
    return $this->belongsTo(Customer::class);
  }

  public function invoice()
  {
    return $this->belongsTo(Invoice::class);
  }
}
