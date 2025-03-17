<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BranchUser extends Pivot
{
  protected $table = 'branch_user';
  protected $fillable = ['branch_id', 'user_id'];

  public function branch()
  {
    return $this->belongsTo(Branch::class);
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
