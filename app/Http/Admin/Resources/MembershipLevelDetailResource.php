<?php

namespace App\Http\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipLevelDetailResource extends JsonResource
{
  /**
   * @param Request $request
   */
  public function toArray($request): array
  {
    return array_merge(parent::toArray($request), [
      'upgrade_reward_content'  =>  $this->upgrade_reward_content,
      'birthday_gift' =>  $this->birthday_gift,
      'party_booking_offer' =>  $this->party_booking_offer,
      'shopping_entertainment_offers' =>  $this->shopping_entertainment_offers,
      'new_product_offers'  =>  $this->new_product_offers,
      'end_of_year_gifts' =>  $this->end_of_year_gifts,
    ]);
  }
}
