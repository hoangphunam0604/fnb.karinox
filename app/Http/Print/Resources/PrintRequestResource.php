<?php

namespace App\Http\Print\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrintRequestResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'  => $this->id,
      'code' => $this->code,
      'customer_name' => $this->customer_name,
      'total_price' => $this->total_price,
      'print_requested_at' => $this->print_requested_at?->format('d/m/Y H:i:s'),
      'print_count' => $this->print_count,
      'last_printed_at' => $this->last_printed_at?->format('d/m/Y H:i:s'),
    ];
  }
}
