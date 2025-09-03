<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBranchResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'branch_name' => $this->name,
      'branch_id' => $this->id,
      'is_selling' => (bool) ($this->pivot->is_selling ?? false),
      'stock_quantity' => (int) $this->pivot->stock_quantity
    ];
  }
}
