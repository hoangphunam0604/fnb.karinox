<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransactionItem extends Model
{
  use HasFactory;

  protected $fillable = [
    'transaction_id',
    'inventory_transaction_id', // Keep for backward compatibility
    'product_id',
    'quantity',
    'cost_price',
    'sale_price',
  ];


  public function transaction()
  {
    return $this->belongsTo(InventoryTransaction::class, 'inventory_transaction_id');
  }

  public function product()
  {
    return $this->belongsTo(Product::class);
  }
}
