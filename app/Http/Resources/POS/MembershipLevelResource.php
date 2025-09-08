<?php

namespace App\Http\Resources\POS;

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
      'birthday_gift' =>  $this->birthday_gift,
      'party_booking_offer' =>  $this->party_booking_offer,
      'shopping_entertainment_offers' =>  $this->shopping_entertainment_offers,
      'new_product_offers' =>  $this->new_product_offers,
      'end_of_year_gifts' =>  $this->end_of_year_gifts,
    ];
  }
}
