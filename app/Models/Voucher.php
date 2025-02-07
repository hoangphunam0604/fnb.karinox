<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
  use HasFactory;

  protected $fillable = [
    'code',
    'description',
    'amount',
    'type',
    'start_date',
    'end_date',
    'usage_limit',
    'used_count',
    'is_active',
  ];

  /**
   * Kiểm tra xem voucher có còn hiệu lực không.
   *
   * @return bool
   */
  public function isValid()
  {
    return $this->is_active &&
      ($this->usage_limit === null || $this->used_count < $this->usage_limit) &&
      ($this->start_date === null || now()->gte($this->start_date)) &&
      ($this->end_date === null || now()->lte($this->end_date));
  }
}
