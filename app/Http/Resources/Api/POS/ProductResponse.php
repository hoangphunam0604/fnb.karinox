<?php

namespace App\Http\Resources\Api\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResponse extends JsonResource
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
      'price' => $this->price,
      'allows_sale' => $this->allows_sale,
      'is_reward_point' => $this->is_reward_point,
      'product_type' => $this->product_type,
      'toppings' => ProductToppingResponse::collection($this->whenLoaded('toppings')),
    ];
  }
}
