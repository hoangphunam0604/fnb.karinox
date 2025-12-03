<?php

namespace App\Http\Admin\Resources;

use Illuminate\Http\Request;

class ProductDetailResource extends ProductResource
{
  public function toArray(Request $request): array
  {
    return array_merge(parent::toArray($request), [
      'branches'  =>  ProductBranchResource::collection($this->whenLoaded('branches')),
      'attributes' => ProductAttributeResource::collection($this->whenLoaded('attributes')),
      'toppings' => ProductToppingResource::collection($this->whenLoaded('toppings')),
      'formulas' => ProductFormulaResource::collection($this->whenLoaded('formulas')),
    ]);
  }
}
