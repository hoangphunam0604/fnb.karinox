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
    //info
    'product_type', //Loại sản phẩm: nguyên liệu, hàng hoá, hàng chế biến, combo, dịch vụ
    'category_id', //danh mục sản phẩm
    'code',
    'barcode',
    'name',
    'description', // Mô tả
    'cost_price', //Giá nhập, giá gốc
    'regular_price', //Giá bán
    'sale_price', // Giá giảm
    'unit', //Đơn vị
    'status', //Bán | ngừng bán
    'allows_sale', //Bán trực tiếp
    'is_reward_point', //Tích điểm
    'is_topping', //Có thể sử dụng làm topping
    'print_label', // In tem
    'print_kitchen', // In phiếu bếp
    'thumbnail',
    'manage_stock', //Cho phép quản lý tồn kho
    'sell_branches' //Danh sách chi nhánh cần quản lý tồn kho
  ];

  protected $casts = [
    'cost_price' => 'int',
    'regular_price' => 'int',
    'sale_price' => 'int',
    'allows_sale' => 'boolean',
    'is_reward_point' => 'boolean',
    'is_topping' => 'boolean',
    'manage_stock' => 'boolean',
    'print_label' => 'boolean',
    'print_kitchen' => 'boolean',
    'product_type'  =>   ProductType::class,
    'status'  => CommonStatus::class,
    'sell_branches' => 'array',
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
    static::saving(function ($product) {
      // chuẩn hoá để luôn là mảng ID duy nhất, kiểu int
      if (is_array($product->sell_branches)) {
        $ids = array_values(array_unique(array_map('intval', $product->sell_branches)));
        $product->sell_branches = $ids;
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
}
