<?php

namespace App\Http\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransactionResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'branch_id' => $this->branch_id,
      'branch_name' => $this->branch?->name,
      'transaction_type' => $this->transaction_type?->value,
      'transaction_type_label' => $this->transaction_type?->getLabel(),
      'note' => $this->note,
      'reference_id' => $this->reference_id,
      'destination_branch_id' => $this->destination_branch_id,
      'destination_branch_name' => $this->destinationBranch?->name,
      'created_at' => $this->created_at?->toDateTimeString(),
      'updated_at' => $this->updated_at?->toDateTimeString(),
      
      // Relations
      'items' => InventoryTransactionItemResource::collection($this->whenLoaded('items')),
    ];
  }
}
