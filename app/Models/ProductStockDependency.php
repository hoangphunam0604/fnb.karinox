<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStockDependency extends Model
{
  use HasFactory;

  protected $fillable = [
    'source_product_id',
    'target_product_id',
    'quantity_ratio',
  ];

  protected $casts = [
    'quantity_ratio' => 'decimal:3',
  ];

  /**
   * Sản phẩm gốc (combo/processed/service)
   */
  public function sourceProduct(): BelongsTo
  {
    return $this->belongsTo(Product::class, 'source_product_id');
  }

  /**
   * Sản phẩm cần trừ kho (goods/ingredient)
   */
  public function targetProduct(): BelongsTo
  {
    return $this->belongsTo(Product::class, 'target_product_id');
  }
}
