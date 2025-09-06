<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipLevel extends Model
{
  use HasFactory;
  protected $fillable = [
    'rank',
    'name',
    'min_spent',
    'max_spent',
    'reward_multiplier',
    'upgrade_reward_content',
    'birthday_gift',
    'party_booking_offer',
    'shopping_entertainment_offers',
    'new_product_offers',
    'end_of_year_gifts',
  ];
  protected $casts = [
    'rank' => 'integer',
    'min_spent' => 'integer',
    'max_spent' => 'integer',
    'reward_multiplier' => 'float',
  ];
}
