<?php

namespace App\Http\Print\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrintJobResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'type' => $this->type,
      'content' => $this->content,
      'device_id' => $this->device_id,
      'priority' => $this->priority,
      'status' => $this->status,
      'error_message' => $this->error_message,
      'retry_count' => $this->retry_count,
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'processed_at' => $this->processed_at?->format('Y-m-d H:i:s'),
      'order' => $this->whenLoaded('order', function () {
        return [
          'id' => $this->order->id,
          'order_code' => $this->order->order_code,
          'table_name' => $this->order->table?->name,
          'total_amount' => $this->order->total_amount,
        ];
      }),
    ];
  }
}
