<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
      'membership_level_id' => $this->membership_level_id,
      'membership_level' =>  new MembershipLevelResource($this->whenLoaded('membershipLevel')),
      'loyalty_card_number' =>  $this->loyalty_card_number,
      'loyalty_points'  =>  $this->loyalty_points,
      'reward_points' =>  $this->reward_points,
      'used_reward_points'  =>  $this->used_reward_points,
      'total_spent' =>  $this->total_spent,
      'last_purchase_at'  =>  $this->last_purchase_at,
      'last_birthday_bonus_date'  =>  $this->last_birthday_bonus_date,
      'fullname'  =>  $this->fullname,
      'email' =>  $this->email,
      'phone' =>  $this->phone,
      'address' =>  $this->address,
      'birthday'  =>  $this->birthday,
      'gender'  =>  $this->gender,
      'avatar'  =>  $this->avatar,
      'is_birthday_today'  =>  $this->isBirthdayToday(),
      'can_receive_new_member_gifts'  => $this->canReceiveNewMemberGift(),
      'can_receive_birthday_gifts' =>  $this->canReceiveBirthdayGifts(),
      'is_eligible_for_birthday_bonus' => $this->isEligibleForBirthdayBonus(),
    ];
  }
}
