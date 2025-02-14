<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointHistory extends Model
{
  use HasFactory;

  protected $fillable = [
    'customer_id',
    'transaction_type',
    'previous_loyalty_points',
    'previous_reward_points',
    'loyalty_points_changed',
    'reward_points_changed',
    'loyalty_points_after',
    'reward_points_after',
    'source_type',
    'source_id',
    'usage_type',
    'usage_id',
    'note'
  ];

  public function customer()
  {
    return $this->belongsTo(Customer::class);
  }
}
