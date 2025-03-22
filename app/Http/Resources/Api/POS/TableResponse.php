<?php

namespace App\Http\Resources\Api\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableResponse extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'name'  =>  $this->name,
      'area_id' =>  $this->area_id,
      'capacity'  =>  $this->capacity,
      'note'  =>  $this->note,
      'status'  =>  $this->status
    ];
  }
}
