<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'name' => $this->name,
      'category_id' => $this->category_id,
      'price' => $this->price,
      'product_type' => $this->product_type,
      'allows_sale' => $this->allows_sale,
      'is_reward_point' => $this->is_reward_point,
      'is_topping' => $this->is_topping,

      'category' => new CategoryResource($this->whenLoaded('category')),
      'attributes' => ProductAttributeResource::collection($this->whenLoaded('attributes')),
      'toppings' => ProductResource::collection($this->whenLoaded('toppings')),
      'formulas' => ProductFormulaResource::collection($this->whenLoaded('formulas')),
    ];
  }
}
