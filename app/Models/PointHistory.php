<?php

namespace App\Models;

use App\Enums\CustomerPointType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointHistory extends Model
{
  use HasFactory;

  protected $fillable = [
    'customer_id',
    'type',
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

  protected $casts = [
    'type' => CustomerPointType::class,
  ];

  public function customer()
  {
    return $this->belongsTo(Customer::class);
  }
}
