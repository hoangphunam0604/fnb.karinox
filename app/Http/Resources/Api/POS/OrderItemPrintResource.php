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
      'Ma_Hang' => $this->product->code ?? '',
      'Ten_Hang_Hoa' => $this->product->name ?? '',
      'Ghi_Chu_Hang_Hoa' => $this->getToppingsTextAndNote(),
      'So_Luong' => $this->quantity,
      'Don_Gia' => number_format($this->unit_price),
      'Thanh_Tien' => number_format($this->total_price),
    ];
  }
  protected function getToppingsTextAndNote(): string
  {
    $this->loadMissing('toppings');

    return $this->toppings
      ->map(function ($topping) {
        return ($topping->topping_name ?? 'Topping') . ' x' . $topping->quantity;
      })
      ->implode(', ') . ($this->note ? "<br>{$this->note}" : '');
  }
}
