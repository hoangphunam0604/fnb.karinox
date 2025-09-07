<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'branch_id' =>  $this->branch_id,
      'order_id'  =>  $this->order_id,
      'code'  =>  $this->code,
      'subtotal_price'  =>  $this->subtotal_price,
      'discount_amount' =>  $this->discount_amount,
      'reward_discount' =>  $this->reward_discount,
      'total_price' =>  $this->total_price,
      'paid_amount' =>  $this->paid_amount,
      'change_amount' =>  $this->change_amount,
      'tax_rate'  =>  $this->tax_rate,
      'tax_amount'  =>  $this->tax_amount,
      'total_price_without_vat' =>  $this->total_price_without_vat,
      'reward_points_used'  =>  $this->reward_points_used,
      'earned_loyalty_points' =>  $this->earned_loyalty_points,
      'earned_reward_points'  =>  $this->earned_reward_points,
      'voucher_id'  =>  $this->voucher_id,
      'sales_channel' =>  $this->sales_channel,
      'invoice_status'  =>  $this->invoice_status,
      'payment_status'  =>  $this->payment_status,
      'payment_method'  =>  $this->payment_method,
      'note'  =>  $this->note,
      'customer_id' =>  $this->customer_id,
      'loyalty_card_number' =>  $this->loyalty_card_number,
      'customer_name' =>  $this->customer_name,
      'customer_phone'  =>  $this->customer_phone,
      'customer_email'  =>  $this->customer_email,
      'customer_address'  =>  $this->customer_address,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
      'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
    ];
  }
}
