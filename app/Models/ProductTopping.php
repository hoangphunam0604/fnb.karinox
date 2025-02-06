<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTopping extends Model
{
  use HasFactory;

  protected $fillable = [
    'product_id',
    'topping_product_id',
    'quantity', // Số lượng topping cho sản phẩm
  ];


  public function product()
  {
    return $this->belongsTo(Product::class);
  }

  public function toppingProduct()
  {
    return $this->belongsTo(Product::class, 'topping_product_id');
  }
}
