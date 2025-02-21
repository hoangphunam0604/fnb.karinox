<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitchenTicketItem extends Model
{
  use HasFactory;
  protected $fillable = [
    'kitchen_ticket_id',
    'order_item_id',
    'product_id',
    'quantity',
    'status',
    'note'
  ];

  public function ticket()
  {
    return $this->belongsTo(KitchenTicket::class);
  }

  public function product()
  {
    return $this->belongsTo(Product::class);
  }
}
