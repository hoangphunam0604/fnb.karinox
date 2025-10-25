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

  // Loại cần in: order hoặc invoice
  public $type;
  // Id của order hoặc invoice cần in
  public $id;
  // Chi nhánh cần in
  public $branchId;
  /**
   * Create a new event instance.
   */
  public function __construct(string $type, int $id,  int $branchId)
  {
    $this->id = $id;
    $this->type = $type;
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
      'id' => $this->id
    ];
  }
}
