<?php

namespace App\Http\Print\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoicePrintResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
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

      // Thông tin hóa đơn đầy đủ
      'invoice' => [
        'id' => $this->id,
        'code' => $this->code,
        'order_code' => $this->order?->code,
        'table_name' => $this->table_name ?? 'N/A',
        'subtotal_price' => $this->subtotal_price,
        'discount_amount' => $this->discount_amount,
        'reward_discount' => $this->reward_discount,
        'total_price' => $this->total_price,
        'paid_amount' => $this->paid_amount,
        'change_amount' => $this->change_amount,
        'tax_rate' => $this->tax_rate,
        'tax_amount' => $this->tax_amount,
        'payment_method' => $this->payment_method,
        'reward_points_used' => $this->reward_points_used,
        'earned_loyalty_points' => $this->earned_loyalty_points,
        'earned_reward_points' => $this->earned_reward_points,
        'note' => $this->note,
        'created_at' => $this->created_at->format('d/m/Y H:i:s'),
        'items' => InvoiceItemPrintResource::collection($this->items)
      ]
    ];
  }
}
