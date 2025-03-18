<?php

namespace App\Http\Resources\Api\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'id' => $this->id,
      'order_id' => $this->order_id,
      'product_id' => $this->product_id,
      'product_name' => $this->product_name,
      'quantity' => $this->quantity,
      'unit_price' => $this->unit_price,
      'total_price' => $this->total_price,
      'final_price' => $this->total_price_with_topping,
      'status' => $this->status,
      'note' => $this->note,
      'toppings' => OrderToppingResource::collection($this->toppings ?? []),
    ];
  }
}
