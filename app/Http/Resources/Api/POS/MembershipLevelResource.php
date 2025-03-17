<?php

namespace App\Http\Resources\Api\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class MembershipLevelResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array<string, mixed>
   */
  public function toArray($request)
  {
    return [
      'id'  =>  $this->id,
      'name'  =>  $this->name,
      'min_spent' =>  $this->min_spent,
      'max_spent' =>  $this->max_spent,
      'reward_multiplier' =>  $this->reward_multiplier,
      'upgrade_reward_content'  =>  $this->upgrade_reward_content,
    ];
  }
}
