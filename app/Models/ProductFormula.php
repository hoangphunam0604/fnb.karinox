<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFormula extends Model
{
  use HasFactory;

  protected $fillable = [
    'product_id', // Sản phẩm chính (combo)
    'ingredient_product_id', // Sản phẩm thành phần
    'quantity', // Số lượng của thành phần trong combo
  ];


  public function product()
  {
    return $this->belongsTo(Product::class);
  }

  public function ingredientProduct()
  {
    return $this->belongsTo(Product::class, 'ingredient_product_id');
  }
}
