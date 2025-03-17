<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceTopping extends Model
{
  use HasFactory;

  protected $fillable = [
    'invoice_item_id',
    'topping_id',
    'topping_name',
    'quantity',
    'unit_price',
    'total_price',
  ];

  /**
   * Mối quan hệ với sản phẩm trong hóa đơn
   */
  public function invoiceItem()
  {
    return $this->belongsTo(InvoiceItem::class);
  }
}
