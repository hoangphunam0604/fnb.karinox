<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
  use HasFactory;

  public $incrementing = false; // Không sử dụng id tự tăng
  protected $primaryKey = ['product_id', 'attribute_id']; // Định nghĩa khóa chính là 2 cột
  public $timestamps = false; // Giữ timestamps nếu cần theo dõi thời gian cập nhật

  protected $fillable = [
    'product_id',
    'attribute_id',
    'value',
  ];

  public function product()
  {
    return $this->belongsTo(Product::class);
  }

  public function attribute()
  {
    return $this->belongsTo(Attribute::class);
  }
}
