<?php

namespace App\Http\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductFormulaResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'product_id' => $this->product_id,
      'ingredient_id' => $this->ingredient_id,
      'quantity' => $this->quantity,
      // Quan hệ: sản phẩm chính
      'product' => new ProductResource($this->whenLoaded('product')),

      // Quan hệ: thành phần (ingredient)
      'ingredient' => new ProductResource($this->whenLoaded('ingredient')),
    ];
  }
}
