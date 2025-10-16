<?php

namespace App\Http\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockReportResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'product_id' => $this->product_id,
      'product_name' => $this->product?->name,
      'product_code' => $this->product?->code,
      'product_unit' => $this->product?->unit,
      'product_type' => $this->product?->product_type?->value,
      'branch_id' => $this->branch_id,
      'stock_quantity' => $this->stock_quantity,
      'min_stock' => $this->min_stock,
      'max_stock' => $this->max_stock,
      'is_low_stock' => $this->stock_quantity < $this->min_stock,
      'is_out_of_stock' => $this->stock_quantity <= 0,
    ];
  }
}
