<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
  public function toArray($request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'parent_id' => $this->parent_id,
      'description' => $this->description,
      'created_at' => $this->created_at,
    ];
  }
}
