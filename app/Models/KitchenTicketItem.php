<?php

namespace App\Models;

use App\Enums\KitchenTicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitchenTicketItem extends Model
{
  use HasFactory;
  protected $fillable = [
    'kitchen_ticket_id',
    'order_item_id',
    'product_id',
    'product_name',
    'product_price',
    'toppings_text',
    'note',
    'quantity',
    'status',
  ];

  protected $casts = [
    'status' => KitchenTicketStatus::class,
  ];

  public function ticket()
  {
    return $this->belongsTo(KitchenTicket::class, 'kitchen_ticket_id');
  }

  public function product()
  {
    return $this->belongsTo(Product::class);
  }
}
