<?php

namespace App\Http\POS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
  public function toArray($request)
  {
    return [
      'id' => $this->id,
      'order_id' => $this->order_id,
      'order_code' => $this->order->order_code ?? null,
      'table_id' => $this->table_id,
      'table_name' => $this->table->name ?? null,
      'user_id' => $this->user_id,
      'receiver_id' => $this->receiver_id,
      'customer_id' => $this->customer_id,
      'customer_name' => $this->customer->name ?? null,
      'type' => $this->type,
      'status' => $this->status,
      'start_time' => $this->start_time?->format('Y-m-d H:i:s'),
      'end_time' => $this->end_time?->format('Y-m-d H:i:s'),
      'duration_hours' => $this->duration_hours,
      'order_item_id' => $this->order_item_id,
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
