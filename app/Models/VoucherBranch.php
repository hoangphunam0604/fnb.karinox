<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherBranch extends Model
{
  use HasFactory;

  protected $fillable = ['voucher_id', 'branch_id'];

  public function voucher()
  {
    return $this->belongsTo(Voucher::class);
  }

  public function branch()
  {
    return $this->belongsTo(Branch::class);
  }
}
