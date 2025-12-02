<?php

namespace App\Http\Admin\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
  public function toArray($request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
    ];
  }
}
