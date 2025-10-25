<?php

namespace App\Http\Print\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KitchenPrintResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    // Lọc các items cần in phiếu bếp
    $kitchenItems = $this->items->filter(function ($item) {
      return $item->product && $item->product->print_kitchen === true;
    });

    return [
      // Thông tin nhân viên
      'staff' => [
        'name' => $this->staff?->name ?? 'N/A'
      ],

      // Thông tin khách hàng
      'customer' => [
        'name' => $this->customer?->name ?? $this->customer_name ?? 'Khách lẻ',
        'membership_level' => $this->customer?->membershipLevel?->name ?? 'N/A',
        'loyalty_points' => $this->customer?->loyalty_points ?? 0,
        'reward_points' => $this->customer?->reward_points ?? 0
      ],

      // Thông tin hóa đơn (chỉ items cho bếp)
      'invoice' => [
        'id' => $this->id,
        'code' => $this->code,
        'order_code' => $this->order?->code,
        'table_name' => $this->table_name ?? 'N/A',
        'note' => $this->note,
        'created_at' => $this->created_at->format('d/m/Y H:i:s'),
        'items' => InvoiceItemPrintResource::collection($kitchenItems),
        'priority' => 'high'
      ]
    ];
  }
}
