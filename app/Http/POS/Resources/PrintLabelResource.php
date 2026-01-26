<?php

namespace App\Http\POS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrintLabelResource extends JsonResource
{
  /**
   * Transform the resource into an array for label printing.
   * Maps OrderItem data to template variables used in label print templates.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public function toArray($request)
  {
    // This resource is for individual items, so items will be an array of single items
    return [
      'items' => $this->map(function ($item) {
        return [
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
