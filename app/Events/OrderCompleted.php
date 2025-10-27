<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class OrderCompleted implements ShouldBroadcastNow
{
  use Dispatchable, SerializesModels;

  public Order $order;
  public bool $print;

  public function __construct(Order $order, $print = false)
  {
    $this->order = $order;
    $this->print = $print;
  }

  public function broadcastOn(): Channel
  {
    return new Channel('order.' . $this->order->id);
  }

  public function broadcastAs(): string
  {
    return 'order.completed';
  }

  /**
   * Get the data to broadcast.
   */
  public function broadcastWith(): array
  {
    return [
      'order' => $this->order
    ];
  }
}
