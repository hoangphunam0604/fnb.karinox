<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class TableAndRoomResource extends JsonResource
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
      'name' => $this->name,
      'address' => $this->address,
      'phone' => $this->phone_number,
    ];
  }
}
