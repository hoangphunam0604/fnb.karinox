<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCompleted implements ShouldBroadcast
{
  use Dispatchable, SerializesModels;

  public Order $order;

  public function __construct(Order $order)
  {
    $this->order = $order;
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
