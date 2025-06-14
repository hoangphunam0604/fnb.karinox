<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductAttributeResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'product_id' => $this->product_id,
      'attribute_id' => $this->attribute_id,
      'value' => $this->value,
      'attribute' => new AttributeResource($this->whenLoaded('attribute')),
    ];
  }
}
