<?php

namespace App\Http\Resources\Api\Kitchen;

use Illuminate\Http\Resources\Json\JsonResource;

class KitchenTicketItemResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public function toArray($request)
  {
    return [
      'id' => $this->id,
      'order_code' => $this->ticket_id ? $this->ticket->order->order_code : "",
      'table_name' => $this->ticket_id ? $this->ticket->table->name : "Mang đi",
      'product_id' => $this->product_id,
      'product_name' => $this->product_name,
      'quantity' => $this->quantity,
      'toppings_text' => $this->toppings_text,
      'note' => $this->note,
      'status' => $this->status,
    ];
  }
}
