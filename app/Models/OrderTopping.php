<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTopping extends Model
{
  use HasFactory;

  protected $fillable = [
    'order_item_id',
    'topping_id',
    'quantity',
    'unit_price',
    'total_price',
  ];

  protected $casts = [
    'order_item_id' => 'integer',
    'topping_id' => 'integer',
    'quantity' => 'integer',
    'unit_price' => 'integer',
    'total_price' => 'integer',
  ];

  /**
   * Mối quan hệ với sản phẩm trong đơn hàng
   */
  public function orderItem()
  {
    return $this->belongsTo(OrderItem::class);
  }
}
