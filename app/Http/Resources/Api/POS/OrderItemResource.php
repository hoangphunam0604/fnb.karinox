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
            'product_name' => $this->product->name,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'subtotal' => $this->quantity * $this->price,
            'toppings' => OrderToppingResource::collection($this->whenLoaded('toppings')),
        ];
    }
}
