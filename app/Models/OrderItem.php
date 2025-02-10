<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
  use HasFactory;

  protected $fillable = [
    'order_id',
    'product_id',
    'quantity',
    'unit_price',
    'total_price'
  ];

  /**
   * Mối quan hệ với đơn hàng
   */
  public function order()
  {
    return $this->belongsTo(Order::class);
  }

  /**
   * Mối quan hệ với topping
   */
  public function toppings()
  {
    return $this->hasMany(OrderTopping::class);
  }
}
