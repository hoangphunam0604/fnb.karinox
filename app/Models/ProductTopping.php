<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTopping extends Model
{
  use HasFactory;

  public $incrementing = false; // Không sử dụng id tự tăng
  protected $primaryKey = ['product_id', 'ingredient_id']; // Định nghĩa khóa chính là 2 cột
  public $timestamps = false; // Giữ timestamps nếu cần theo dõi thời gian cập nhật


  protected $fillable = [
    'product_id',
    'topping_id',
  ];


  public function product()
  {
    return $this->belongsTo(Product::class);
  }

  public function topping()
  {
    return $this->belongsTo(Product::class, 'topping_id');
  }
}
