<?php

namespace App\Http\POS\Resources;

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
      'product_price' => $this->product_price,
      'sale_price' => $this->sale_price,
      'discount_type' => $this->discount_type,
      'discount_percent' => $this->discount_percent,
      'discount_amount' => $this->discount_amount,
      'quantity' => $this->quantity,
      'unit_price' => $this->unit_price,
      'total_price' => $this->total_price,
      'status' => $this->status,
      'note' => $this->note,
      'printed_label' =>  $this->printed_label,
      'printed_kitchen' =>  $this->printed_kitchen,
      'toppings' => OrderToppingResource::collection($this->toppings ?? []),
    ];
  }
}
