<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
  protected $fillable = [
    'order_id',
    'table_id',
    'user_id',
    'receiver_id',
    'customer_id',
    'type',
    'status',
    'start_time',
    'end_time',
    'duration_hours',
    'order_item_id',
  ];

  protected $casts = [
    'type' => BookingType::class,
    'status' => BookingStatus::class,
    'start_time' => 'datetime',
    'end_time' => 'datetime',
    'duration_hours' => 'integer',
  ];

  public function order(): BelongsTo
  {
    return $this->belongsTo(Order::class);
  }

  public function table(): BelongsTo
  {
    return $this->belongsTo(TableAndRoom::class, 'table_id');
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function receiver(): BelongsTo
  {
    return $this->belongsTo(User::class, 'receiver_id');
  }

  public function customer(): BelongsTo
  {
    return $this->belongsTo(Customer::class);
  }

  public function orderItem(): BelongsTo
  {
    return $this->belongsTo(OrderItem::class);
  }
}
