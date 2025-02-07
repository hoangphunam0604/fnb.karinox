<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderHistory extends Model
{
  use HasFactory;

  protected $fillable = [
    'order_id',
    'old_status',
    'new_status',
    'user_id',
    'notes'
  ];

  public function order()
  {
    return $this->belongsTo(Order::class)->withDefault();
  }

  public function user()
  {
    return $this->belongsTo(User::class)->withDefault();
  }
}
