<?php

namespace App\Http\POS\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryProductResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  Request  $request
   * @return array
   */
  public function toArray($request)
  {
    return [
      'category' => $this['category'],
      'products' => ProductResource::collection(collect($this['products'])),
    ];
  }
}
