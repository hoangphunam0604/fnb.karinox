<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceCompleted implements ShouldBroadcastNow
{
  use Dispatchable, SerializesModels;

  public Invoice $invoice;

  public function __construct(Invoice $invoice)
  {
    $this->invoice = $invoice;
  }

  /**
   * Get the channels the event should broadcast on.
   */
  public function broadcastOn(): array
  {
    // Broadcast đến public channel theo order_id để frontend có thể listen
    return [
      new Channel("order.{$this->invoice->order_id}")
    ];
  }

  /**
   * The event's broadcast name.
   */
  public function broadcastAs(): string
  {
    return 'invoice.completed';
  }

  /**
   * Get the data to broadcast.
   */
  public function broadcastWith(): array
  {
    return [
      'invoice_id' => $this->invoice->id,
      'order_id' => $this->invoice->order_id
    ];
  }
}
