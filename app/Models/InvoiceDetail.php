<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
  use HasFactory;

  protected $fillable = [
    'invoice_id',
    'product_id',
    'quantity',
    'price',
    'total_price',
    'note',
    'parent_id',
  ];

  public function invoice()
  {
    return $this->belongsTo(Invoice::class);
  }

  public function product()
  {
    return $this->belongsTo(Product::class);
  }

  public function toppings()
  {
    return $this->hasMany(InvoiceDetail::class, 'parent_id');
  }

  public function mainProduct()
  {
    return $this->belongsTo(InvoiceDetail::class, 'parent_id');
  }
}
