<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipUpgradeHistory extends Model
{
  use HasFactory;

  protected $fillable = [
    'customer_id',
    'old_membership_level_id',
    'new_membership_level_id',
    'upgraded_at',
    'upgrade_reward_content',
    'reward_claimed',
  ];

  protected $casts = [
    'reward_claimed' => 'boolean'
  ];
  public function customer()
  {
    return $this->belongsTo(Customer::class);
  }

  public function oldMembershipLevel()
  {
    return $this->belongsTo(MembershipLevel::class, 'old_membership_level_id');
  }

  public function newMembershipLevel()
  {
    return $this->belongsTo(MembershipLevel::class, 'new_membership_level_id');
  }
}
