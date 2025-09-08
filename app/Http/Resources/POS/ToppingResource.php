<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToppingResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'topping_id' => $this->topping->id,
      'topping_name' => $this->topping->name,
      'price' => $this->topping->price,
    ];
  }
}
