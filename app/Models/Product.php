<?php

namespace App\Models;

use App\Enums\CommonStatus;
use App\Enums\ProductType;
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
    'regular_price',
    'sale_price',
    'unit',
    'status',
    'allows_sale',
    'is_reward_point',
    'is_topping',
    'manage_stock',
    'print_label',
    'print_kitchen',
    'images',
  ];

  protected $casts = [
    'allows_sale' => 'boolean',
    'is_reward_point' => 'boolean',
    'is_topping' => 'boolean',
    'manage_stock' => 'boolean',
    'images' => 'array',
    'product_type'  =>   ProductType::class,
    'status'  => CommonStatus::class,
  ];
  /**
   * Thiết lập mặc định `manage_stock` dựa vào `product_type`
   */
  public static function boot()
  {
    parent::boot();

    static::creating(function ($product) {
      if ($product->product_type === 'goods') {
        $product->manage_stock = true; // 🔥 Chỉ hàng hóa mới bật quản lý tồn kho
      } else {
        $product->manage_stock = false; // 🔥 Các loại khác mặc định không quản lý tồn kho
      }
    });

    static::updating(function ($product) {
      if ($product->product_type !== 'goods') {
        $product->manage_stock = false; // 🔥 Đổi loại khác sẽ tự động tắt quản lý tồn kho
      }
    });
  }
  public function category()
  {
    return $this->belongsTo(Category::class);
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
  public function getPriceAttribute()
  {
    return $this->sale_price ?? $this->regular_price;
  }

  public function getThumbnailAttribute()
  {
    return !empty($this->images) && is_array($this->images) ? $this->images[0] : "https://cdn1-fnb-userdata.kiotviet.vn/2024/02/karinopr/images/7b6c86ec16ce4a11b3fbb9ace3fd05f5";
  }
}
