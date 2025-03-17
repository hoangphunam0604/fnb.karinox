<?php

namespace App\Events;

use App\Models\KitchenTicket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;

class KitchenOrderReady
{
  use  InteractsWithSockets, SerializesModels;

  public KitchenTicket $ticket;

  public function __construct(KitchenTicket $ticket)
  {
    $this->ticket = $ticket;
  }

  /**
   * Get the channels the event should broadcast on.
   *
   * @return array<int, \Illuminate\Broadcasting\Channel>
   */
  public function broadcastOn(): array
  {
    return [new PrivateChannel('kitchen-orders.ready.' . $this->ticket->branch_id)];
  }
}
