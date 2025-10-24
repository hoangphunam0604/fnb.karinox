<?php

namespace App\Models;

use App\Enums\CommonStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
  use HasFactory;

  protected $fillable = [
    'name',
    'phone_number',
    'email',
    'address',
    'status',
    'sort_order',
    'connection_code',
  ];

  protected $casts = [
    'status'  => CommonStatus::class,
    'sort_order'  =>  'integer'
  ];

  public function products()
  {
    return $this->belongsToMany(Product::class, 'product_branches');
  }


  public function users()
  {
    return $this->belongsToMany(User::class, 'branch_user')
      ->using(BranchUser::class);
  }

  /**
   * Tìm chi nhánh theo print connection code
   */
  public static function findByConnectionCode(string $code): ?self
  {
    return static::where('connection_code', $code)
      ->where('status', CommonStatus::ACTIVE)
      ->first();
  }
}
