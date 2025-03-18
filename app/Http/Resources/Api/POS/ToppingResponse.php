<?php

namespace App\Http\Resources\Api\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToppingResponse extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'topping_id' => $this->toppingProduct->id,
      'topping_name' => $this->toppingProduct->name,
      'price' => $this->toppingProduct->price,
    ];
  }
}
