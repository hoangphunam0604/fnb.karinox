<?php

namespace App\Events;

use App\Models\KitchenTicket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KitchenTicketUpdated implements ShouldBroadcastNow
{
  use Dispatchable, SerializesModels;

  public $ticket;

  public function __construct(KitchenTicket $ticket)
  {
    $this->ticket = $ticket->load('items.product');
  }

  public function broadcastOn()
  {
    return new Channel('kitchen-orders');
  }

  public function broadcastWith()
  {
    return [
      'ticket_id' => $this->ticket->id,
      'order_id' => $this->ticket->order_id,
      'table' => $this->ticket->table_id ? $this->ticket->table?->name : 'Mang đi',
      'status' => $this->ticket->status,
      'items' => $this->ticket->items->map(function ($item) {
        return [
          'product_name' => $item->product->name,
          'quantity' => $item->quantity,
          'note' => $item->note,
          'status' => $item->status,
        ];
      }),
    ];
  }
}
