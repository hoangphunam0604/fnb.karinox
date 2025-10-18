<?php

namespace App\Http\Admin\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductStockSummaryResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   */
  public function toArray($request)
  {
    return [
      'product' => [
        'id' => $this->id,
        'code' => $this->code,
        'name' => $this->name,
        'unit' => $this->unit,
        'cost_price' => $this->cost_price,
        'regular_price' => $this->regular_price,
        'category' => [
          'id' => $this->category->id,
          'name' => $this->category->name,
          'code_prefix' => $this->category->code_prefix
        ]
      ],
      'current_stock' => [
        'quantity' => $this->current_stock_quantity ?? 0,
        'value' => ($this->current_stock_quantity ?? 0) * $this->cost_price,
        'last_updated' => $this->stock_last_updated
      ],
      'statistics' => [
        'total_imported' => $this->total_imported ?? 0,
        'total_exported' => $this->total_exported ?? 0,
        'total_sold' => $this->total_sold ?? 0,
        'total_adjusted' => $this->total_adjusted ?? 0,
        'transactions_count' => $this->transactions_count ?? 0
      ],
      'period_summary' => [
        'period' => $this->period ?? null,
        'opening_stock' => $this->opening_stock ?? 0,
        'closing_stock' => $this->closing_stock ?? 0,
        'net_change' => ($this->closing_stock ?? 0) - ($this->opening_stock ?? 0)
      ]
    ];
  }
}
