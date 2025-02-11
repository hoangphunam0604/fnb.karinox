<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
  use HasFactory;

  protected $fillable = [
    'code',
    'type',
    'discount_amount',
    'max_discount',
    'min_order_value',
    'start_date',
    'end_date',
    'usage_limit',
    'per_customer_limit',
    'is_active',
    'applicable_membership_levels',
    'valid_days_of_week',
    'valid_weeks_of_month',
    'valid_months',
    'valid_time_ranges',
    'excluded_dates',
    'warn_if_used',
  ];

  protected $casts = [
    'applicable_membership_levels' => 'array',
    'valid_days_of_week' => 'array',
    'valid_weeks_of_month' => 'array',
    'valid_months' => 'array',
    'valid_time_ranges' => 'array',
    'excluded_dates' => 'array',
  ];

  public function branches()
  {
    return $this->belongsToMany(Branch::class, 'voucher_branches');
  }
}
