<?php

namespace App\Http\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
  /**
   * @param Request $request
   */
  public function toArray($request): array
  {
    return [
      'id' => $this->id,
      'fullname' => $this->fullname,
      'email' => $this->email,
      'phone' => $this->phone,
      'address' => $this->address,
      'birthday' => $this->birthday,
      'gender' => $this->gender,
      'membership_level_id' => $this->membership_level_id,
      'loyalty_card_number' => $this->loyalty_card_number,
      'loyalty_points' => $this->loyalty_points,
      'reward_points' => $this->reward_points,
      'used_reward_points' => $this->used_reward_points,
      'total_spent' => $this->total_spent,
      'status' => $this->status,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
      'arena_member'  =>  $this->arena_member,
      'arena_member_exp'  =>  $this->arena_member_exp ? $this->arena_member_exp->format('d/m/Y') : null,
      'membership_level' => $this->whenLoaded('membershipLevel', function () {
        return $this->membershipLevel->name ?? null;
      }),
    ];
  }
}
