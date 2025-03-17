<?php

namespace App\Http\Resources\Api\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryProductResponse extends JsonResource
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
            'category' => $this['category'],
            'products' => ProductResponse::collection(collect($this['products'])),
        ];
    }
}
