<?php

namespace App\Http\POS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrintKitchenResource extends JsonResource
{
  /**
   * Transform the resource into an array for kitchen printing.
   * Maps Order data to template variables used in kitchen print templates.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public function toArray($request)
  {
    return [
      'Ten_Phong_Ban' => $this->table_name ?? '',
      'Ten_Nhan_Vien' => $this->user->name ?? '',
      'Ma_Dat_Hang' => $this->order_code ?? '',
      'Ngay_Thang_Nam' => $this->created_at ? $this->created_at->format('d/m/Y H:i') : '',

      // Items will be processed by frontend - only include items that need kitchen notification
      'items' => $this->kitchenItems->map(function ($item, $index) {
        return [
          'STT' => $index + 1,
          'Ten_San_Pham' => $item->product_name ?? '',
          'Topping' => $this->formatToppings($item),
          'Ghi_Chu' => $item->note ? "Ghi chú: {$item->note}" : '',
          'So_Luong' => $item->quantity ?? 0,
          'Don_Vi' => $item->product->unit ?? 'Phần',
        ];
      }),
    ];
  }

  /**
   * Format toppings for display
   */
  protected function formatToppings($item)
  {
    if (!$item->toppings || $item->toppings->isEmpty()) {
      return '';
    }

    $toppingList = $item->toppings->map(function ($topping) {
      return "• {$topping->topping_name}";
    })->join('<br>');

    return $toppingList ? "<br>{$toppingList}" : '';
  }
}
