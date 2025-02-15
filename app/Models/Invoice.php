<?php

namespace App\Models;

use App\Events\InvoiceCompleted;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
  use HasFactory;
  protected $fillable = [
    'branch_id',
    'order_id',

    'total_amount',
    'paid_amount',
    'change_amount',
    'used_reward_points',
    'reward_points_value',

    'voucher_id',
    'sales_channel',
    'invoice_status',
    'payment_status',
    'payment_method',
    'note',
    'customer_id',
    'loyalty_card_number',
    'customer_name',
    'customer_phone',
    'customer_email',
    'customer_address',
  ];
  protected static function boot()
  {
    parent::boot();

    static::creating(function ($invoice) {
      if (!$invoice->code)
        $invoice->code = self::generateInvoiceCode($invoice->branch_id);
    });
    static::updated(function ($invoice) {
      if ($invoice->status === 'completed' && $invoice->getOriginal('status') !== 'completed') {
        event(new InvoiceCompleted($invoice));
      }
    });
  }

  public static function generateInvoiceCode($branchId)
  {
    $latestOrder = self::whereDate('created_at', now()->toDateString())
      ->where('branch_id', $branchId)
      ->orderBy('id', 'desc')
      ->first();

    $increment = $latestOrder ? ((int) substr($latestOrder->order_code, -4)) + 1 : 1;

    return sprintf("HD-%02d-%s-%04d", $branchId, now()->format('ymd'), $increment);
  }
  public function items()
  {
    return $this->hasMany(InvoiceItem::class);
  }

  public function order()
  {
    return $this->belongsTo(Order::class);
  }

  public function voucher()
  {
    return $this->belongsTo(Voucher::class)->withDefault();
  }

  /**
   * Mối quan hệ với khách hàng
   */
  public function customer()
  {
    return $this->belongsTo(Customer::class);
  }

  /**
   * Kiểm tra hóa đơn đã thanh toán đầy đủ chưa
   */
  public function isPaid()
  {
    return $this->payment_status === 'paid';
  }

  /**
   * Kiểm tra hóa đơn đã hoàn tất chưa
   */
  public function isCompleted()
  {
    return $this->invoice_status === 'completed';
  }

  /**
   * Đánh dấu hóa đơn là hoàn tất nếu đã thanh toán đầy đủ
   */
  public function markAsCompleted()
  {
    if ($this->isPaid()) {
      $this->invoice_status = 'completed';
      $this->save();
    }
  }
}
