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
      'invoice_code' => $this->invoice->code,
      'order_code' => $this->invoice->order?->code,
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
