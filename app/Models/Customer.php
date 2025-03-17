<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Enums\Gender;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
  use HasFactory;

  protected $fillable = [
    'id',
    'membership_level_id',
    'loyalty_card_number',
    'loyalty_points',
    'reward_points',
    'used_reward_points',
    'total_spent',
    'last_purchase_at',
    'last_birthday_bonus_date',

    'status',
    'fullname',
    'email',
    'phone',
    'address',
    'birthday',
    'gender',

    'referral_code',
    'avatar',
    'company_name',
    'tax_id',
    'facebook_id',
    'zalo_id',
    'signup_source',
    'note',
  ];

  protected $casts = [
    'loyalty_points' => 'integer',
    'reward_points' => 'integer',
    'used_reward_points' => 'integer',
    'total_spent' => 'integer',
    'status' => CustomerStatus::class,
    'gender'  =>  Gender::class,
  ];

  public function membershipLevel()
  {
    return $this->belongsTo(MembershipLevel::class, 'membership_level_id')->withDefault(['name' => 'Silver']);
  }


  public function pointHistories()
  {
    return $this->hasMany(PointHistory::class);
  }


  /**
   * Kiểm tra hôm nay có phải là ngày sinh nhật của khách hàng hay không.
   */
  public function isBirthdayToday(): bool
  {
    if (!$this->birthday) {
      return false;
    }

    return Carbon::now()->format('m-d') === Carbon::parse($this->birthday)->format('m-d');
  }

  /**
   * Kiểm tra khách hàng có đủ điều kiện nhận thêm tích điểm theo hạng không
   */
  public function isEligibleForBirthdayBonus()
  {
    $today = Carbon::now();

    // Kiểm tra xem hôm nay có phải là sinh nhật không
    if (!$this->isBirthdayToday()) {
      return false;
    }

    // Chưa nhận bonus thì cho phép
    if (!$this->last_birthday_bonus_date)
      return true;

    // Nếu đã nhận X2 trong hôm nay, tiếp tục cho phép
    if (Carbon::parse($this->last_birthday_bonus_date)->toDateString() == $today->toDateString()) {
      return true;
    }

    // Nếu đã nhận X2 trong năm nay, không cho nhận lại
    if (Carbon::parse($this->last_birthday_bonus_date)->year == $today->year) {
      return false;
    }

    return true;
  }
}
