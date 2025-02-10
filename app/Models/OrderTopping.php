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
    'price',
  ];

  /**
   * Mối quan hệ với sản phẩm trong đơn hàng
   */
  public function orderItem()
  {
    return $this->belongsTo(OrderItem::class);
  }
}
