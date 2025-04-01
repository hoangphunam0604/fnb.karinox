<?php

namespace App\Http\Resources\Api\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemPrintResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'STT' => $this->whenNotNull($this->resource->index ?? null), // optional nếu có đánh số thứ tự
      'Ten_Hang_Hoa' => $this->product->name ?? '',
      'Ghi_Chu_Hang_Hoa' => $this->note ?? '',
      'So_Luong' => $this->quantity,
      'Don_Gia' => number_format($this->unit_price),
      'Thanh_Tien' => number_format($this->total_price),
    ];
  }
}
