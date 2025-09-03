<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBranchResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'product_id' => $this->product_id,
      'branch_id' => $this->branch_id,
      'is_selling' => $this->is_selling ?? false,
      'stock_quantity' => $this->stock_quantity
    ];
  }
}
