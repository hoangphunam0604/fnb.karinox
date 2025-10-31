<?php

namespace App\Http\Print\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabelPrintResource extends JsonResource
{
  private $item;
  private $invoice;

  public function __construct($item, $invoice)
  {
    parent::__construct($item);
    $this->item = $item;
    $this->invoice = $invoice;
  }

  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'Ma_Don_Hang' => $this->invoice->code,
      'Nhan_Vien_Ban_Hang' => $this->staff->fullname,
      'Ngay_Thang_Nam' => $this->invoice->created_at->format('d/m/Y H:i:s'),
      'Ma_Dat_Hang' => $this->invoice->order?->code,
      'Ten_Khach_Hang' => $this->invoice->customer_name ?? 'Khách lẻ',
      'table_name' => $this->invoice->table_name ?? 'N/A',
      'product' => [
        'id' => $this->item->product_id,
        'name' => $this->item->product_name,
        'quantity' => $this->item->quantity,
        'unit_price' => $this->item->unit_price,
        'total_price' => $this->item->total_price,
        'toppings_text' => $this->item->toppings_text
      ],
      'created_at' => $this->invoice->created_at->format('d/m/Y H:i:s')
    ];
  }
}
