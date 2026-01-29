<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductArenaService
{
  /**
   * Cài đặt dịch vụ Arena
   * - Nếu product_id null: xoá dịch vụ có arena_type tương ứng (set về 'none')
   * - Nếu product_id có giá trị: set arena_type cho sản phẩm đó
   * 
   * @param array $services
   * @return array
   */
  public function setArenaServices(array $services): array
  {
    return DB::transaction(function () use ($services) {
      $removedCount = 0;
      $setCount = 0;

      foreach ($services as $service) {
        $arenaType = $service['arena_type'];
        $productId = $service['product_id'] ?? null;

        if ($productId === null) {
          // Xoá dịch vụ: reset tất cả sản phẩm có arena_type này về 'none'
          $count = Product::where('arena_type', $arenaType)
            ->update(['arena_type' => 'none']);
          $removedCount += $count;
        } else {
          // Cài đặt dịch vụ: set arena_type cho sản phẩm
          Product::where('id', $productId)
            ->update(['arena_type' => $arenaType]);
          $setCount++;
        }
      }

      // Lấy danh sách dịch vụ sau khi cập nhật
      return $this->getArenaServices()->toArray();
    });
  }

  /**
   * Lấy danh sách các dịch vụ Arena đã được cài đặt
   * 
   * @return \Illuminate\Support\Collection
   */
  public function getArenaServices()
  {
    return Product::where('arena_type', '!=', 'none')
      ->select('id', 'code', 'name', 'arena_type', 'price')
      ->get()
      ->map(function ($product) {
        return [
          'product_id' => $product->id,
          'product_code' => $product->code,
          'product_name' => $product->name,
          'arena_type' => $product->arena_type->value,
          'arena_type_label' => $product->arena_type->getLabel(),
          'price' => $product->price,
        ];
      });
  }
}
