<?php

namespace App\Models;

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
    'unit_price',
    'quantity',
    'total_price',
  ];
  protected $casts = [
    'product_id' => 'integer',
    'product_price' => 'integer',
    'unit_price' => 'integer',
    'quantity' => 'integer',
    'total_price' => 'integer',
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
