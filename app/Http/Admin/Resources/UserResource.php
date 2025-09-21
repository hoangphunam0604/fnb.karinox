<?php

namespace App\Http\Admin\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   */
  public function toArray($request): array
  {
    return [
      'id' => $this->id,
      'fullname' => $this->fullname,
      'username' => $this->username,
      'is_active' => (bool) $this->is_active,
      'last_seen_at' => $this->last_seen_at ? $this->last_seen_at->toDateTimeString() : null,

      'created_at' => $this->created_at?->toDateTimeString(),
      'updated_at' => $this->updated_at?->toDateTimeString(),
    ];
  }
}
