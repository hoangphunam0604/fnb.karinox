<?php

namespace App\Http\Print\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{

  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'Ten_San_Pham'  =>  $this->product_name,
      'Topping' =>  $this->toppings_text,
      'Ghi_Chu' =>  $this->note,
      'So_Luong'  =>  $this->quantity,
      'Don_Gia' =>  $this->unit_price,
      'Thanh_Tien'  =>  $this->total_price
    ];
  }
}
