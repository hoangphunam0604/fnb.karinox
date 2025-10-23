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
      'print_id' => $this->print_id,
      'type' => $this->type,
      'metadata' => $this->metadata,
      'status' => $this->status,
      'requested_at' => $this->requested_at?->format('Y-m-d H:i:s'),
      'printed_at' => $this->printed_at?->format('Y-m-d H:i:s'),
      'confirmed_at' => $this->confirmed_at?->format('Y-m-d H:i:s'),
      'branch' => $this->whenLoaded('branch', function () {
        return [
          'id' => $this->branch->id,
          'name' => $this->branch->name,
          'address' => $this->branch->address,
        ];
      }),
    ];
  }
}
