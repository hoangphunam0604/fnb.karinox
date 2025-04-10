<?php

namespace App\Events;

use App\Models\KitchenTicket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class KitchenTicketUpdated implements ShouldBroadcastNow
{
  use Dispatchable, SerializesModels;

  public $ticket;

  public function __construct(KitchenTicket $ticket)
  {
    $this->ticket = $ticket->load(['table', 'order', 'items']);
  }

  public function broadcastOn()
  {
    return new Channel("kitchen.branch.{$this->ticket->branch_id}");
  }
  public function broadcastAs(): string
  {
    return 'kitchen.updated';
  }

  public function broadcastWith()
  {
    Log::info('kitchen.updated');
    $order_code = $this->ticket->order_id;
    $table_name = $this->ticket->table_id ? $this->ticket->table->name : 'Mang đi';
    return [
      'ticket_id' => $this->ticket->id,
      'status' => $this->ticket->status,
      'items' => $this->ticket->items->map(function ($item) use ($order_code, $table_name) {
        return [
          'id' => $item->id,
          'order_code' => $order_code,
          'table_name' => $table_name,
          'product_id' => $item->product_id,
          'product_name' => $item->product_name,
          'quantity' => $item->quantity,
          'toppings_text' => $item->toppings_text,
          'note' => $item->note,
          'status' => $item->status,
        ];
      }),
    ];
  }
}
