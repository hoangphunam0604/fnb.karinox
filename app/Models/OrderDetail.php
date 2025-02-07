<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
  use HasFactory;

  protected $fillable = [
    'order_id',
    'product_id',
    'product_name',
    'quantity',
    'unit_price',
    'total_price',
  ];

  /**
   * Quan hệ với Order
   */
  public function order()
  {
    return $this->belongsTo(Order::class)->withDefault();
  }

  /**
   * Quan hệ với Product
   */
  public function product()
  {
    return $this->belongsTo(Product::class)->withDefault();
  }
}
