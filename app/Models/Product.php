<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
  use HasFactory;

  protected $fillable = [
    'product_group',
    'product_type',
    'category_id',
    'code',
    'barcode',
    'name',
    'description',
    'cost_price',
    'price',
    'unit',
    'allows_sale',
    'is_reward_point',
    'is_topping',
    'images',
    'status',
  ];

  protected $casts = [
    'allows_sale' => 'boolean',
    'is_reward_point' => 'boolean',
    'is_topping' => 'boolean',
  ];

  public function categories()
  {
    return $this->belongsToMany(Category::class, 'category_product');
  }

  public function branches()
  {
    return $this->belongsToMany(Branch::class, 'product_branches')->withPivot('stock_quantity');
  }

  public function attributes()
  {
    return $this->belongsToMany(Attribute::class, 'product_attributes')
      ->withPivot('value');
  }

  public function formulas()
  {
    return $this->hasMany(ProductFormula::class);
  }

  public function toppings()
  {
    return $this->hasMany(ProductTopping::class);
  }
}
