<?php

namespace App\Http\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductToppingResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'product_id' => $this->product_id,
      'topping_id' => $this->topping_id,
      // Quan hệ: sản phẩm chính
      'product' => new ProductResource($this->whenLoaded('product')),

      // Quan hệ: món thêm (topping)
      'topping' => new ProductResource($this->whenLoaded('topping')),
    ];
  }
}
