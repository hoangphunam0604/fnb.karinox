<?php

namespace App\Http\POS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
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
      'note' => $this->note,
      'tables' => TableResource::collection($this->tablesAndRooms)->resolve()
    ];
  }
}
