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
    'quantity',
    'unit_price',
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
