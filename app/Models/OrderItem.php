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
    'product_price',
    'unit_price',
    'quantity',
    'total_price',
    'status',
    'note',
    'print_label',
    'print_kitchen',
    'printed',
    'printed_at',
  ];

  protected $casts = [
    'product_id' => 'integer',
    'product_price' => 'integer',
    'unit_price' => 'integer',
    'quantity' => 'integer',
    'total_price' => 'integer',
    'status' => OrderItemStatus::class,
    'print_label' =>  'boolean',
    'print_kitchen' =>  'boolean',
    'printed'  =>  'boolean',
    'printed_at' =>  'datetime',
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
