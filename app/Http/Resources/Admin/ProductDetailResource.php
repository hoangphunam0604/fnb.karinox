<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class ProductDetailResource extends ProductResource
{
  public function toArray(Request $request): array
  {
    return array_merge(parent::toArray($request), [
      'category' => new CategoryResource($this->whenLoaded('category')),
      'branches'  =>  ProductBranchResource::collection($this->whenLoaded('branches')),
      'attributes' => ProductAttributeResource::collection($this->whenLoaded('attributes')),
      'toppings' => ProductResource::collection($this->whenLoaded('toppings')),
      'formulas' => ProductFormulaResource::collection($this->whenLoaded('formulas')),
    ]);
  }
}
