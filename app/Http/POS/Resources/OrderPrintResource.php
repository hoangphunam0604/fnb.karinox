<?php

namespace App\Http\POS\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderPrintResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'Nhan_Vien_Ban_Hang' => $this->created_by?->name ?? 'N/A',
      'Ten_Phong_Ban' => $this->table?->name ?? 'Mang đi',
      'Tong_Diem_Hien_Tai' => $this->customer?->reward_points ?? 0,
      'Ngay_Thang_Nam' => $this->created_at->format('d/m/Y H:i'),
      'Ma_Don_Hang' => $this->code ?? ('ORD-' . $this->id),
      'Khach_Hang' => $this->customer?->name ?? 'Khách lẻ',
      'Tong_Diem_Sau_Hoa_Don' => $this->customer?->reward_points ?? 0,
      'items' => OrderItemPrintResource::collection($this->items),
      'Tong_Tien_Hang' => number_format($this->total_price),
      'Tong_Thanh_Toan_Sau_Diem' => number_format($this->final_price),
      'Tong_Tien_Hang_Tru_CKHD' => number_format($this->final_price), // có thể thay đổi nếu có giảm giá
    ];
  }
}
