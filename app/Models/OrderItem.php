<?php

namespace App\Models;

use App\Enums\OrderItemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
  use HasFactory;

  protected $fillable = [
    'order_id',
    'product_id',
    'product_name',
    'quantity',
    'unit_price',
    'total_price',
    'total_price_with_topping',
    'status'
  ];

  protected $casts = [
    'order_item_id' => 'integer',
    'product_id' => 'integer',
    'quantity' => 'integer',
    'unit_price' => 'integer',
    'total_price' => 'integer',
    'total_price_with_topping' => 'integer',
    'status' => OrderItemStatus::class,
  ];



  /**
   * Mối quan hệ với đơn hàng
   */
  public function order()
  {
    return $this->belongsTo(Order::class);
  }

  /**
   * Mối quan hệ với đơn hàng
   */
  public function product()
  {
    return $this->belongsTo(Product::class);
  }

  /**
   * Mối quan hệ với topping
   */
  public function toppings()
  {
    return $this->hasMany(OrderTopping::class);
  }
}
