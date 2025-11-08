<?php

namespace App\Http\Print\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvisionalResource extends JsonResource
{

  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'Ma_Dat_Hang'  =>  $this->order_code,
      'Nhan_Vien_Ban_Hang'  =>  $this->staff->fullname,
      'Ngay_Thang_Nam'  =>  $this->created_at->format('d/m/Y H:i:s'),
      'Ten_Phong_Ban'  =>  $this->table_name,
      'Ten_Khach_Hang'  =>  $this->customer_name,
      'Ma_Khach_Hang'  =>  $this->loyalty_card_number,
      'Diem_Tich_Luy'  =>  $this->customer ? $this->customer->reward_points : 0,
      'Diem_Thuong'  =>  $this->customer ? $this->customer->loyalty_points : 0,
      'Kenh_Ban_Hang'  =>  $this->sales_channel,
      'Phuong_Thuc_Thanh_Toan'  =>  $this->payment_method,
      'Tong_Tien_Hang'  =>  $this->total_price,
      'Ma_Giam_Gia'  =>  $this->voucher_code,
      'Tien_Giam_Gia'  =>  $this->voucher_discount,
      'Diem_Thuong_Su_Dung'  =>  $this->reward_points_used,
      'Tien_Diem_Thuong_Su_Dung'  =>  $this->reward_discount,
      'Tong_Thanh_Toan'  =>  $this->total_price,
      'Ghi_Chu' =>  $this->note,
      'items' =>  ItemResource::collection($this->items),
    ];
  }
}
