<?php

namespace App\Http\Resources\Api\Kitchen;

use Illuminate\Http\Resources\Json\JsonResource;

class KitchenTicketResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public function toArray($request)
  {
    return [
      'id' => $this->id,
      'status' => $this->status,
      'note' => $this->note,
      'tables' => KitchenTicketItemResource::collection($this->items)
    ];
  }
}
