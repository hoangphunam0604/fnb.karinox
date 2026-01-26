<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\ProductType;
use App\Enums\ProductBookingType;
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
    'product_type', //Loại sản phẩm: nguyên liệu, hàng hoá, hàng chế biến, combo, dịch vụ
    'booking_type', //Đặt vé
    'unit_price',
    'sale_price',
    'discount_type',
    'discount_percent',
    'discount_amount',
    'discount_note',
    'quantity',
    'total_price',
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
    'product_type' => ProductType::class,
    'booking_type' => ProductBookingType::class,
    'discount_type' => DiscountType::class,
    'product_id' => 'integer',
    'product_price' => 'float',
    'unit_price' => 'float',
    'sale_price' => 'float',
    'discount_percent' => 'float',
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

  /**
   * Tính toán discount và prices dựa trên discount_type
   * Nếu type='percent': discount_amount = unit_price * discount_percent / 100
   * Nếu type='fixed': discount_percent = (discount_amount / unit_price) * 100
   * sale_price = unit_price - discount_amount
   * total_price = sale_price * quantity
   */
  public function calculatePrices(): void
  {
    $unitPrice = $this->unit_price ?? 0;
    $quantity = $this->quantity ?? 1;
    $this->sale_price = $unitPrice; // Mặc định sale_price = unit_price

    // Tính discount_amount và discount_percent dựa trên discount_type
    if ($this->discount_type === DiscountType::PERCENT) {
      $this->discount_percent = $this->discount_percent ?? 0;
      $this->discount_amount = round(($unitPrice * $this->discount_percent) / 100, 2);
    } elseif ($this->discount_type === DiscountType::FIXED) {
      $this->discount_amount = $this->discount_amount ?? 0;
      // Tính discount_percent từ discount_amount
      $this->discount_percent = $unitPrice > 0 ? round(($this->discount_amount / $unitPrice) * 100, 2) : 0;
    } else {
      $this->discount_percent = 0;
      $this->discount_amount = 0;
    }

    // Đảm bảo discount_amount không âm và không vượt quá unit_price
    $this->discount_amount = max(0, min($this->discount_amount, $unitPrice));
    if ($this->discount_amount) {
      // Tính sale_price và total_price
      $this->sale_price = round($unitPrice - $this->discount_amount, 2);
    }
    $this->total_price = round($this->sale_price * $quantity, 2);
  }

  /**
   * Cập nhật unit_price khi có toppings và tính lại prices
   */
  public function recalculateWithToppings(): void
  {
    // Cập nhật unit_price = product_price + tổng toppings
    $this->unit_price = $this->product_price + $this->toppings->sum('total_price');

    // Tính lại discount và prices
    $this->calculatePrices();
  }

  protected static function booted()
  {
    // Auto calculate prices before saving
    static::saving(function (self $item) {
      $item->calculatePrices();
    });
  }
}
