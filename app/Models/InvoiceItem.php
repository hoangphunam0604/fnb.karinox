<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\ProductType;
use App\Enums\ProductArenaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
  use HasFactory;
  protected $fillable = [
    'invoice_id',
    'product_id',
    'product_name',
    'product_price',
    'product_type',
    'arena_type',
    'unit_price',
    'discount_type',
    'discount_percent',
    'discount_amount',
    'discount_note',
    'discount_for_new_product',
    'sale_price',
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
    'arena_type' => ProductArenaType::class,
    'discount_type' => DiscountType::class,
    'product_id' => 'integer',
    'product_price' => 'float',
    'unit_price' => 'float',
    'sale_price' => 'float',
    'discount_percent' => 'float',
    'discount_amount' => 'float',
    'quantity' => 'integer',
    'total_price' => 'float',
    'discount_for_new_product' => 'boolean',
    'print_label' =>  'boolean',
    'printed_label' =>  'boolean',
    'printed_label_at'  =>  'datetime',
    'print_kitchen' =>  'boolean',
    'printed_kitchen' =>  'boolean',
    'printed_kitchen_at'  =>  'datetime',
  ];
  /**
   * Mối quan hệ với hóa đơn
   */
  public function invoice()
  {
    return $this->belongsTo(Invoice::class);
  }

  /**
   * Mối quan hệ với sản phẩm
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
    return $this->hasMany(InvoiceTopping::class);
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
}
