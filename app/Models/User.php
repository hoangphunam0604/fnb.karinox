<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
  /** @use HasFactory<\Database\Factories\UserFactory> */
  use HasFactory, Notifiable, HasRoles;

  /**
   * The attributes that are mass assignable.
   *
   * @var list<string>
   */
  protected $fillable = [
    'fullname',
    'username',
    'password',
    'current_branch',
    'is_active',
    'last_seen_at',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var list<string>
   */
  protected $hidden = [
    'password',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'password' => 'hashed',
    ];
  }

  public function branches()
  {
    return $this->belongsToMany(Branch::class, 'branch_user')
      ->using(BranchUser::class);
  }

  public function managesBranch($branchId): bool
  {
    return $this->branches()->where('branches.id', $branchId)->exists();
  }
  public function currentBranch()
  {
    return $this->belongsTo(Branch::class, 'current_branch');
  }


  public function getLoginRedirectAttribute()
  {
    if (!$this->current_branch) {
      return route('branches.index');
    }
    switch ($this->getRoleNames()->first()) {
      case UserRole::ADMIN->value:
      case UserRole::MANAGER->value:
        return route('admin.dashboard');
      case UserRole::KITCHEN_STAFF->value:
        return route('kitchen.orders');
      case UserRole::CASHIER->value:
        return route('pos.tables');
      default:
        return route('welcome');
    }
  }

  public function getJWTIdentifier()
  {
    return $this->getKey();
  }

  public function getJWTCustomClaims()
  {
    return [];
  }
}
