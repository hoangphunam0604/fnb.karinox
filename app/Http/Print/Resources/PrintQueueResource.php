<?php

namespace App\Http\Print\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrintQueueResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'order_id' => $this->order_id,
      'type' => $this->type,
      'content' => $this->content,
      'device_id' => $this->device_id,
      'priority' => $this->priority,
      'status' => $this->status,
      'error_message' => $this->error_message,
      'retry_count' => $this->retry_count,
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
      'processed_at' => $this->processed_at?->format('Y-m-d H:i:s'),
      'order_info' => $this->whenLoaded('order', function () {
        return [
          'order_code' => $this->order->order_code,
          'table_name' => $this->order->table?->name,
          'total_amount' => number_format($this->order->total_amount, 0, ',', '.') . 'đ',
          'status' => $this->order->status,
        ];
      }),
    ];
  }
}
