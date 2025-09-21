<?php

namespace App\Http\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceToppingResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'invoice_item_id' => $this->invoice_item_id,
      'topping_id' => $this->topping_id,
      'topping_name' => $this->topping_name,
      'quantity' => $this->quantity,
      'unit_price' => $this->unit_price,
      'total_price' => $this->total_price,
    ];
  }
}
