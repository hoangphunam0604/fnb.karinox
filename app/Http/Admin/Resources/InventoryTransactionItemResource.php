<?php

namespace App\Http\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransactionItemResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'transaction_id' => $this->transaction_id,
      'product_id' => $this->product_id,
      'product_name' => $this->product?->name,
      'product_code' => $this->product?->code,
      'product_unit' => $this->product?->unit,
      'quantity' => $this->quantity,
      'created_at' => $this->created_at?->toDateTimeString(),
    ];
  }
}
