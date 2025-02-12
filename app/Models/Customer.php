<?php

namespace App\Models;

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
    'status',
    'name',
    'email',
    'phone',
    'address',
    'dob',
    'gender',
    'last_purchase_at',
    'referral_code',
    'avatar',
    'company_name',
    'tax_id',
    'facebook_id',
    'zalo_id',
    'signup_source',
    'note',
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
}
