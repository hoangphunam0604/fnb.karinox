<?php

namespace App\Http\Common\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
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
      'type' => $this->type,
      'name' => $this->name,
      'address' => $this->address,
      'phone' => $this->phone_number,
    ];
  }
}
