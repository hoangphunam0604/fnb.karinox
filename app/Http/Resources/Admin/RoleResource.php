<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
  public function toArray($request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'guard_name' => $this->guard_name,
      'permissions' => $this->whenLoaded('permissions', function () {
        return $this->permissions->map(fn($p) => [
          'id' => $p->id,
          'name' => $p->name,
        ]);
      }, $this->permissions ?? []),
      'created_at' => $this->created_at?->toDateTimeString(),
      'updated_at' => $this->updated_at?->toDateTimeString(),
    ];
  }
}
