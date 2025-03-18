<?php

namespace App\Http\Resources\Api\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderToppingResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'id' => $this->id,
      'order_item_id' => $this->order_item_id,
      'topping_id' => $this->topping_id,
      'topping_name' => $this->topping_name,
      'price' => $this->unit_price,
      'quantity' => $this->quantity,
    ];
  }
}
