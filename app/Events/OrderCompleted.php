<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\PrivateChannel;
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

  public function broadcastOn(): PrivateChannel
  {
    return new PrivateChannel('order.' . $this->order->id);
  }

  public function broadcastAs(): string
  {
    return 'order.completed';
  }
}
