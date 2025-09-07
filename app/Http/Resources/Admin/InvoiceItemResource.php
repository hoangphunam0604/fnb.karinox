<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'invoice_id'  =>  $this->invoice_id,
      'product_id'  =>  $this->product_id,
      'product_name'  =>  $this->product_name,
      'product_price' =>  $this->product_price,
      'unit_price'  =>  $this->unit_price,
      'quantity'  =>  $this->quantity,
      'total_price' =>  $this->total_price,
      'toppings' => InvoiceToppingResource::collection($this->whenLoaded('toppings')),
    ];
  }
}
