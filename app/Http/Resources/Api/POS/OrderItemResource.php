<?php

namespace App\Http\Resources\Api\POS;

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
      'status' => $this->status,
      'note' => $this->note,
      'print_label' =>  $this->print_label,
      'print_kitchen' =>  $this->print_kitchen,
      'toppings' => OrderToppingResource::collection($this->toppings ?? []),
    ];
  }
}
