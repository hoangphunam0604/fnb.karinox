<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceCreated implements ShouldBroadcastNow
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  public Invoice $invoice;

  /**
   * Create a new event instance.
   */
  public function __construct(Invoice $invoice)
  {
    $this->invoice = $invoice;
  }

  public function broadcastOn(): Channel
  {
    return new Channel('order.' . $this->invoice->order_id);
  }

  public function broadcastAs(): string
  {
    return 'invoice.completed';
  }
}
