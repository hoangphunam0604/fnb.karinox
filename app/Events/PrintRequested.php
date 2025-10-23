<?php

namespace App\Events;

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

  public $printData;
  public $branchId;
  public $printId;

  /**
   * Create a new event instance.
   */
  public function __construct(array $printData, int $branchId)
  {
    $this->printData = $printData;
    $this->branchId = $branchId;
    $this->printId = uniqid('print_' . time() . '_');

    // Lưu lịch sử in
    $this->savePrintHistory();
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
      'print_id' => $this->printId,
      'type' => $this->printData['type'],
      'metadata' => $this->printData['metadata'] ?? [],
      'timestamp' => now()->toISOString()
    ];
  }

  /**
   * Lưu lịch sử in
   */
  private function savePrintHistory()
  {
    \App\Models\PrintHistory::create([
      'print_id' => $this->printId,
      'branch_id' => $this->branchId,
      'type' => $this->printData['type'],
      'metadata' => $this->printData['metadata'] ?? [],
      'status' => 'requested',
      'requested_at' => now()
    ]);
  }
}
