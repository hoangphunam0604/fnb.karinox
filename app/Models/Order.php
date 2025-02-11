<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Order extends Model
{
  use HasFactory;

  protected $fillable = [
    'order_code',
    'ordered_at',
    'creator_id',
    'receiver_id',
    'customer_id',
    'branch_id',
    'table_id',
    'total_price',
    'discount_amount',
    'voucher_id',
    'voucher_code',
    'order_status',
    'note',
  ];

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($order) {
      if (!$order->order_code)
        $order->order_code = self::generateOrderCode($order->branch_id);
    });

    static::updating(function ($order) {
      if ($order->isDirty('order_status')) {
        OrderHistory::create([
          'order_id'   => $order->id,
          'old_status' => $order->getOriginal('order_status'),
          'new_status' => $order->order_status,
          'user_id'    => Auth::id(),
          'note'      => 'Cập nhật trạng thái đơn hàng.'
        ]);
      }
    });
  }
  public static function generateOrderCode($branchId)
  {
    $latestOrder = self::whereDate('created_at', now()->toDateString())
      ->where('branch_id', $branchId)
      ->orderBy('id', 'desc')
      ->first();

    $increment = $latestOrder ? ((int) substr($latestOrder->order_code, -4)) + 1 : 1;

    return sprintf("ORD-%02d-%s-%04d", $branchId, now()->format('ymd'), $increment);
  }

  public function receiver()
  {
    return $this->belongsTo(User::class, 'receiver_id')->withDefault();
  }

  public function creator()
  {
    return $this->belongsTo(User::class, 'creator_id')->withDefault();
  }

  public function table()
  {
    return $this->belongsTo(TableAndRoom::class, 'table_id')->withDefault();
  }

  public function branch()
  {
    return $this->belongsTo(Branch::class)->withDefault();
  }

  public function histories()
  {
    return $this->hasMany(OrderHistory::class);
  }

  public function items()
  {
    return $this->hasMany(OrderItem::class);
  }

  public function voucher()
  {
    return $this->belongsTo(Voucher::class)->withDefault([]);
  }

  /**
   * Kiểm tra đơn hàng đã hoàn tất chưa
   */
  public function isCompleted()
  {
    return $this->order_status === 'completed';
  }
}
