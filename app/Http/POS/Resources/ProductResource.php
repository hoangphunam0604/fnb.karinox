<?php

namespace App\Http\POS\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
      'id' => $this->id,
      'code' => $this->code,
      'name' => $this->name,
      'sale_price' => $this->sale_price,
      'regular_price' => $this->regular_price,
      'price' => $this->price,
      'thumbnail' => $this->thumbnail,
      'is_reward_point' => $this->is_reward_point,
      'product_type' => $this->product_type,
      'booking_type' => $this->booking_type,
      'toppings' => ToppingResource::collection($this->whenLoaded('toppings')),
    ];
  }
}
