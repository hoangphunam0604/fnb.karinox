<?php

namespace App\Models;

use App\Enums\CommonStatus;
use App\Enums\ProductArenaType;
use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Reverb\Loggers\Log;

class Product extends Model
{
  use HasFactory;

  protected $fillable = [
    'kiotviet_id',
    //'product_group',
    //info
    'product_type', //Loại sản phẩm: nguyên liệu, hàng hoá, hàng chế biến, combo, dịch vụ
    'menu_id', //danh mục sản phẩm
    'code',
    'name',
    'description', // Mô tả
    'price', //Giá nhập, giá gốc
    'unit', //Đơn vị
    'status', //Bán | ngừng bán
    'arena_type', //Đặt vé
    'allows_sale', //Bán trực tiếp
    'is_reward_point', //Tích điểm
    'is_topping', //Có thể sử dụng làm topping
    'is_new', // Sản phẩm mới
    'print_label', // In tem
    'print_kitchen', // In phiếu bếp
    'thumbnail',
    'arena_discount',
  ];

  protected $casts = [
    'product_type' => ProductType::class,
    'arena_type' => ProductArenaType::class,
    'price' => 'int',
    'allows_sale' => 'boolean',
    'is_reward_point' => 'boolean',
    'is_topping' => 'boolean',
    'is_new' => 'boolean',
    'print_label' => 'boolean',
    'print_kitchen' => 'boolean',
    'arena_discount' => 'array',
    'status'  => CommonStatus::class
  ];
  /**
   * Thiết lập mặc định `manage_stock` dựa vào `product_type`
   */
  public static function boot()
  {
    parent::boot();

    static::saving(function ($product) {
      $product->menu_id = $product->menu_id ?? 1; // Mặc định danh mục sản phẩm
      // chuẩn hoá để luôn là mảng ID duy nhất, kiểu int
      if (is_array($product->sell_branches)) {
        $ids = array_values(array_unique(array_map('intval', $product->sell_branches)));
        $product->sell_branches = $ids;
      }
    });
  }
  public function menu()
  {
    return $this->belongsTo(Menu::class);
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

  /**
   * Kiểm tra sản phẩm có phải là booking item không
   */
  public function isBookingProduct(): bool
  {
    return $this->arena_type === ProductArenaType::FULL_SLOT;
  }
}
