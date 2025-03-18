<?php

namespace App\Http\Resources\Api\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array<string, mixed>
   */
  public function toArray($request)
  {
    return [
      'id' => $this->id,
      'table_id' => $this->table_id,
      'order_code' => $this->order_code,
      'ordered_at' => $this->ordered_at,
      'creator_id' => $this->creator_id,
      'receiver_id' => $this->receiver_id,
      'customer_id' => $this->customer_id,
      'customer' => $this->customer_id ? new CustomerResource($this->whenLoaded('customer')) : null,
      'subtotal_price' => $this->subtotal_price,
      'discount_amount' => $this->discount_amount,
      'reward_points_used' => $this->reward_points_used,
      'reward_discount' => $this->reward_discount,
      'total_price' => $this->total_price,
      'voucher_id' => $this->voucher_id,
      'voucher_code' => $this->voucher_code,
      'order_status' => $this->order_status,
      'payment_method' => $this->payment_method,
      'note' => $this->note,
      'items' => OrderItemResource::collection($this->whenLoaded('items')),
    ];
  }
}
