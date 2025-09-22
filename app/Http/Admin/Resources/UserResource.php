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
      'role' => $this->whenLoaded('roles', function () {
        return $this->roles->pluck('name')->first();
      }),
      'role_name' => $this->whenLoaded('roles', function () {
        $role = $this->roles->pluck('name')->first();
        return $role ? \App\Enums\UserRole::tryFrom($role)?->name() : null;
      }),
      'permissions' => $this->whenLoaded('permissions', function () {
        return $this->permissions->pluck('name');
      }),
      'created_at' => $this->created_at?->toDateTimeString(),
      'updated_at' => $this->updated_at?->toDateTimeString(),
    ];
  }
}
