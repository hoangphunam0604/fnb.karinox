<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrintRequested implements ShouldBroadcast
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  // Loại cần in: order, invoice, cash-inventory
  public $type;
  // Data chứa thông tin cần in (id cho order/invoice, payload cho cash-inventory)
  public $data;
  // Chi nhánh cần in
  public $branchId;

  /**
   * Create a new event instance.
   */
  public function __construct(string $type, $data, int $branchId)
  {
    $this->type = $type;
    $this->data = $data;
    $this->branchId = $branchId;
  }

  /**
   * Get the channels the event should broadcast on.
   */
  public function broadcastOn(): array
  {
    // Broadcast đến tất cả devices của branch
    return [
      new Channel("print-branch-{$this->branchId}")
    ];
  }

  /**
   * The event's broadcast name.
   */
  public function broadcastAs(): string
  {
    return 'print.requested';
  }

  /**
   * Get the data to broadcast.
   */
  public function broadcastWith(): array
  {
    return [
      'type' => $this->type,
      'data' => $this->data
    ];
  }
}
