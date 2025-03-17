<?php

namespace App\Http\Resources\Api\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductToppingResponse extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'topping_id' => $this->toppingProduct->id,
            'name' => $this->toppingProduct->name,
            'name' => $this->toppingProduct->name,
            'price' => $this->toppingProduct->price,
        ];
    }
}