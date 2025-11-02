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
      'Nhan_Vien_Ban_Hang' => $this->staff->fullname,

      'Ma_Don_Hang' => $this->invoice->code,
      'Ngay_Thang_Nam' => $this->invoice->created_at->format('d/m/Y H:i:s'),

      'Ma_Dat_Ban' => $this->invoice->order_code ?? 'N/A',
      'Ten_Phong_Ban' => $this->invoice->table_name ?? 'N/A',

      'Ten_Khach_Hang' => $this->invoice->customer_name ?? 'Khách lẻ',
      'Ma_Khach_Hang' => $this->invoice->loyalty_card_number ?? '',
      'Diem_Tich_Luy' => $this->invoice?->customer->loyalty_points ?? '',
      'Diem_Thuong' => $this->invoice?->customer->reward_points ?? '',

      'Kenh_Ban_Hang' => $this->invoice->sales_channel ?? 0,
      'Phuong_Thuc_Thanh_Toan' => $this->invoice->payment_method_name ?? "Tiền mặt",
      
      'Tong_Tien_Hang' => $this->invoice->subtotal_price_format,
      'Ma_Giam_Gia' => $this->invoice->voucher_code ?? '-',
      'Tien_Giam_Gia' => $this->invoice->voucher_discount ?? 0,
      'Diem_Thuong_Su_Dung' => $this->invoice->reward_points_used ?? '-',
      'Tien_Diem_Thuong_Su_Dung' => $this->invoice->reward_discount ?? '-',
      'Tong_Thanh_Toan' => $this->invoice->total_price_format,

      'table_name' => $this->invoice->table_name ?? 'N/A',
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
        'voucher_discountt' => $this - voucher_discountt,
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
