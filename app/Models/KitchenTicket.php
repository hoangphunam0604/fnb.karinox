<?php

namespace App\Models;

use App\Enums\KitchenTicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitchenTicket extends Model
{
  use HasFactory;

  protected $fillable = [
    'order_id',
    'branch_id',
    'table_id',
    'status',
    'priority',
    'note',
    'accepted_by',
    'created_by',
    'updated_by'
  ];
  protected $casts = [
    'status' => KitchenTicketStatus::class,
  ];

  public function items()
  {
    return $this->hasMany(KitchenTicketItem::class);
  }

  public function order()
  {
    return $this->belongsTo(Order::class);
  }
}
