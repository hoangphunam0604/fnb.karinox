<?php

namespace App\Http\Admin\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ProductStockCardResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   */
  public function toArray($request)
  {
    return [
      'transaction_id' => $this->id,
      'date' => $this->created_at->format('Y-m-d H:i:s'),
      'type' => $this->transaction_type->value,
      'type_label' => $this->getTypeLabel(),
      'reference_number' => $this->reference_number,
      'quantity_before' => $this->pivot->quantity_before ?? 0,
      'quantity_change' => $this->pivot->quantity ?? 0,
      'quantity_after' => $this->pivot->quantity_after ?? 0,
      'unit_cost' => $this->pivot->unit_cost ?? 0,
      'total_cost' => ($this->pivot->quantity ?? 0) * ($this->pivot->unit_cost ?? 0),
      'note' => $this->note,
      'branch' => [
        'id' => $this->branch->id,
        'name' => $this->branch->name,
        'code' => $this->branch->code
      ],
      'user' => $this->user ? [
        'id' => $this->user->id,
        'fullname' => $this->user->fullname
      ] : null
    ];
  }

  private function getTypeLabel()
  {
    return match ($this->transaction_type->value) {
      'import' => 'Nhập kho',
      'export' => 'Xuất kho',
      'transfer_out' => 'Chuyển đi',
      'transfer_in' => 'Chuyển đến',
      'stocktaking' => 'Kiểm kho',
      'adjustment' => 'Điều chỉnh',
      'sale' => 'Bán hàng',
      'return' => 'Trả hàng',
      default => 'Khác'
    };
  }
}
