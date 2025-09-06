<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipLevelResource extends JsonResource
{
  /**
   * @param Request $request
   */
  public function toArray($request): array
  {
    return [
      'id' => $this->id,
      'rank' => $this->rank,
      'name' => $this->name,
      'min_spent' => $this->min_spent,
      'max_spent' => $this->max_spent,
      'reward_multiplier' => $this->reward_multiplier,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
