<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
  use HasFactory;

  protected $fillable = [
    'name',
    'email',
    'phone',
    'address',
    'dob',
    'points',
    'gender',
    'membership_level',
    'last_purchase_at',
    'total_spent',
    'referral_code',
    'status',
    'avatar',
    'company_name',
    'tax_id',
    'facebook_id',
    'zalo_id',
    'preferences',
    'loyalty_card_number',
    'signup_source',
    'note',
  ];
}
