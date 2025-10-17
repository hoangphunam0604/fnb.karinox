<?php

namespace App\Models;

use App\Enums\CommonStatus;
use App\Enums\ProductType;
use App\Services\ProductCodeService;
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
    'images' //Danh sách chi nhánh cần quản lý tồn kho
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
    'images' => 'array',
  ];
  /**
   * Thiết lập mặc định `manage_stock` dựa vào `product_type`
   */
  public static function boot()
  {
    parent::boot();

    static::creating(function ($product) {
      // Auto-generate product code nếu chưa có
      if (empty($product->code)) {
        $codeService = app(ProductCodeService::class);
        $product->code = $codeService->generateProductCode($product->category_id);
      }

      // ✅ Cho phép cả goods và ingredient quản lý tồn kho
      $productType = $product->product_type instanceof \App\Enums\ProductType
        ? $product->product_type->value
        : $product->product_type;

      if (in_array($productType, ['goods', 'ingredient'])) {
        $product->manage_stock = true;
      } else {
        $product->manage_stock = false; // Các loại khác (processed, combo, service) mặc định không quản lý
      }
    });

    static::updating(function ($product) {
      // ✅ Chỉ force false cho các loại không phải goods/ingredient
      $productType = $product->product_type instanceof \App\Enums\ProductType
        ? $product->product_type->value
        : $product->product_type;

      if (!in_array($productType, ['goods', 'ingredient'])) {
        $product->manage_stock = false;
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
    return $this->belongsToMany(Branch::class, 'product_branches')->withPivot(['is_selling', 'stock_quantity']);
  }

  public function attributes()
  {
    return $this->belongsToMany(Attribute::class, 'product_attributes')->withPivot('value');
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
