<?php

namespace App\Http\POS\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'capacity' => $this->capacity,
      'note' => $this->note,
      'status' => $this->status,
    ];
  }
}
