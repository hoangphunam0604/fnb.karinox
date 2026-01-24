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
    'sale_price',
    'discount_type',
    'discount_value',
    'discount_amount',
    'status',
    'note',
    'print_label',
    'printed_label',
    'printed_label_at',
    'print_kitchen',
    'printed_kitchen',
    'printed_kitchen_at',
  ];

  protected $casts = [
    'product_id' => 'integer',
    'product_price' => 'integer',
    'product_price' => 'float',
    'unit_price' => 'float',
    'sale_price' => 'float',
    'discount_value' => 'float',
    'discount_amount' => 'float',
    'quantity' => 'integer',
    'total_price' => 'float',

    'print_label' =>  'boolean',
    'printed_label' =>  'boolean',
    'printed_label_at'  =>  'datetime',
    'print_kitchen' =>  'boolean',
    'printed_kitchen' =>  'boolean',
    'printed_kitchen_at'  =>  'datetime',
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

  /**
   * Accessor: Format toppings thành text
   * Format: "Topping 1 (5,000đ) x 2, Topping 2 (10,000đ) x 1"
   */
  public function getToppingsTextAttribute(): string
  {
    if (!$this->relationLoaded('toppings') || $this->toppings->isEmpty()) {
      return '';
    }

    return $this->toppings->map(function ($topping) {
      return sprintf(
        '%s (%sđ) x %d',
        $topping->topping_name,
        number_format($topping->price, 0, ',', '.'),
        $topping->quantity
      );
    })->join(', ');
  }

  protected static function booted()
  {
    static::saving(function (self $item) {
      $salePrice = $item->sale_price ?? $item->unit_price ?? 0;
      $quantity = $item->quantity ?? 1;

      // Calculate discount_amount based on discount_type and discount_value
      if ($item->discount_type === 'percent') {
        $item->discount_amount = round($salePrice * $quantity * ($item->discount_value / 100), 2);
      } elseif ($item->discount_type === 'fixed') {
        // assume discount_value is fixed amount per item
        $item->discount_amount = round(($item->discount_value ?? 0) * $quantity, 2);
      } else {
        $item->discount_amount = $item->discount_amount ?? 0;
      }

      // Ensure discount_amount is not negative or greater than subtotal
      $subtotal = round($salePrice * $quantity, 2);
      if ($item->discount_amount < 0) {
        $item->discount_amount = 0;
      }
      if ($item->discount_amount > $subtotal) {
        $item->discount_amount = $subtotal;
      }

      // total_price = sale_price * quantity - discount_amount
      $item->total_price = round($subtotal - $item->discount_amount, 2);
    });
  }
}
