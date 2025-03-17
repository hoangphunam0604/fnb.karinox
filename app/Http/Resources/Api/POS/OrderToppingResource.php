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
            'product_id' => $this->product_id,
            'name' => $this->product->name,
            'price' => $this->price,
            'quantity' => $this->quantity,
        ];
    }
}
