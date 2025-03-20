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
   * Mối quan hệ với topping
   */
  public function toppings()
  {
    return $this->hasMany(InvoiceTopping::class);
  }
}
