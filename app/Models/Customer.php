<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
  use HasFactory;

  protected $fillable = [
    'membership_level_id',
    'loyalty_card_number',
    'loyalty_points',
    'reward_points',
    'used_reward_points',
    'total_spent',
    'last_purchase_at',
    'last_birthday_bonus_date',

    'status',
    'name',
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
  ];

  public function membershipLevel()
  {
    return $this->belongsTo(MembershipLevel::class, 'membership_level_id');
  }

  /**
   * Logic cấp độ thành viên
   */
  public function updateMembershipLevel()
  {
    $points = $this->loyalty_points;

    // Tìm hạng cao nhất mà khách hàng có thể đạt được
    $newLevel = MembershipLevel::where('min_spent', '<=', $points)
      ->where(function ($query) use ($points) {
        $query->whereNull('max_spent')
          ->orWhere('max_spent', '>=', $points);
      })
      ->orderBy('rank', 'desc')
      ->first();

    if ($newLevel && (!$this->membership_level_id || $this->membershipLevel->rank < $newLevel->rank)) {
      // Chỉ cập nhật nếu có thay đổi về hạng
      if ($this->membership_level_id !== $newLevel->id) {
        $this->membership_level_id = $newLevel->id;
        $this->save();
      }
    }
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
