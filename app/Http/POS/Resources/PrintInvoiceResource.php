<?php

namespace App\Http\POS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrintInvoiceResource extends JsonResource
{
  /**
   * Transform the resource into an array for invoice printing.
   * Maps Invoice data to template variables used in print templates.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public function toArray($request)
  {
    return [
      'Ma_Don_Hang' => $this->code ?? '',
      'Nhan_Vien_Ban_Hang' => $this->user->name ?? '',
      'Ngay_Thang_Nam' => $this->created_at ? $this->created_at->format('d/m/Y H:i') : '',
      'Ten_Phong_Ban' => $this->table_name ?? '',

      // Customer info
      'Ten_Khach_Hang' => $this->customer_name ?? 'Khách lẻ',
      'Ma_Khach_Hang' => $this->customer->code ?? '',
      'Diem_Tich_Luy' => $this->customer->loyalty_points ?? 0,
      'Diem_Thuong' => $this->earned_reward_points ?? 0,

      // Pricing
      'Tong_Tien_Hang' => number_format($this->subtotal_price, 0, ',', '.') . ' đ',
      'Ma_Giam_Gia' => $this->voucher_code ?? '',
      'Tien_Giam_Gia' => number_format($this->voucher_discount, 0, ',', '.') . ' đ',
      'Diem_Doi_Thuong' => $this->reward_points_used ?? 0,
      'Tien_Diem_Doi_Thuong' => number_format($this->reward_discount, 0, ',', '.') . ' đ',
      'Tong_Thanh_Toan' => number_format($this->total_price, 0, ',', '.') . ' đ',

      // Items will be processed by frontend
      'items' => $this->items->map(function ($item, $index) {
        return [
          'STT' => $index + 1,
          'Ten_San_Pham' => $item->product_name ?? '',
          'Topping' => $this->formatToppings($item),
          'Ghi_Chu' => $item->note ? "Ghi chú: {$item->note}" : '',
          'So_Luong' => $item->quantity ?? 0,
          'Don_Gia' => number_format($item->unit_price, 0, ',', '.') . ' đ',
          'Thanh_Tien' => number_format($item->total_price, 0, ',', '.') . ' đ',
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
