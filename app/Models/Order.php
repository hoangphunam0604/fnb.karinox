<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Order extends Model
{
  use HasFactory;

  protected $fillable = [
    'ordered_at',
    'creator_id',
    'receiver_id',
    'branch_id',
    'table_id',
    'customer_id',
    'order_code',
    'total_amount',
    'discount_amount',
    'voucher_id',
    'voucher_code',
    'payment_status',
    'status',
    'notes',
  ];

  protected static function boot()
  {
    parent::boot();

    static::updating(function ($order) {
      if ($order->isDirty('status')) {
        OrderHistory::create([
          'order_id'   => $order->id,
          'old_status' => $order->getOriginal('status'),
          'new_status' => $order->status,
          'user_id'    => Auth::id(),
          'notes'      => 'Trạng thái đơn hàng được cập nhật.'
        ]);
      }
    });
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

  public function voucher()
  {
    return $this->belongsTo(Voucher::class)->withDefault([]);
  }
}
