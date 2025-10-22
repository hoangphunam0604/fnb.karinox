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
  public $deviceId;
  public $printId;

  /**
   * Create a new event instance.
   */
  public function __construct(array $printData, int $branchId, ?string $deviceId = null)
  {
    $this->printData = $printData;
    $this->branchId = $branchId;
    $this->deviceId = $deviceId;
    $this->printId = uniqid('print_' . time() . '_');

    // Lưu lịch sử in
    $this->savePrintHistory();
  }

  /**
   * Get the channels the event should broadcast on.
   */
  public function broadcastOn(): array
  {
    // Broadcast đến tất cả devices của branch hoặc device cụ thể
    if ($this->deviceId) {
      return [
        new Channel("print-device-{$this->deviceId}")
      ];
    }

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
      'content' => $this->printData['content'],
      'metadata' => $this->printData['metadata'] ?? [],
      'priority' => $this->printData['priority'] ?? 'normal',
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
      'device_id' => $this->deviceId,
      'type' => $this->printData['type'],
      'content' => $this->printData['content'],
      'metadata' => $this->printData['metadata'] ?? [],
      'priority' => $this->printData['priority'] ?? 'normal',
      'status' => 'requested',
      'requested_at' => now()
    ]);
  }
}
