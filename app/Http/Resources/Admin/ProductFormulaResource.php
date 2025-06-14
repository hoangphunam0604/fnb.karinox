<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductFormulaResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'product_id' => $this->product_id,
      'ingredient_id' => $this->ingredient_id,
      'quantity' => $this->quantity,
      'ingredient' => new ProductResource($this->whenLoaded('ingredient')),
    ];
  }
}
