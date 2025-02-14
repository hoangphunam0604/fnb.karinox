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
    'upgrade_reward_content'
  ];
}
