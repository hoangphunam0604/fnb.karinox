<?php

namespace App\Http\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'menu_id' => $this->menu_id,
      'code' => $this->code,
      'name' => $this->name,
      'price' => $this->price,
      'product_type' => $this->product_type,
      'booking_type' => $this->booking_type,
      'allows_sale' => $this->allows_sale,
      'is_reward_point' => $this->is_reward_point,
      'is_topping' => $this->is_topping,
      'is_new' => $this->is_new,
      'print_label' =>  $this->print_label,
      'print_kitchen' =>  $this->print_kitchen,
      'unit' =>  $this->unit,
      'thumbnail'  =>  $this->thumbnail,
      'manage_stock'  =>  $this->manage_stock,
      'sell_branches' => $this->sell_branches,
      'menu' => new MenuResource($this->whenLoaded('menu')),
    ];
  }
}
