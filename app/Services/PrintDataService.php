<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\KitchenTicket;
use App\Models\PrintLabel;

class PrintDataService
{

  /**
   * Lấy data in tạm tính cho order
   */
  public function getProvisionalData(int $orderId): array
  {
    $order = Order::with([
      'customer',
      'items.product',
      'items.toppings'
    ])->findOrFail($orderId);

    // Trả về data tạm tính trực tiếp
    $metadata = [
      'order_id' => $order->id,
      'order_code' => $order->code,
      'table_name' => $order->table?->name,
      'customer_name' => $order->customer?->name ?? 'Khách lẻ',
      'subtotal_price' => $order->subtotal_price,
      'discount_amount' => $order->discount_amount,
      'total_price' => $order->total_price,
      'note' => $order->note,
      'created_at' => $order->created_at->format('d/m/Y H:i:s'),
      'items' => $order->items->map(function ($item) {
        return [
          'product_name' => $item->product_name,
          'quantity' => $item->quantity,
          'unit_price' => $item->unit_price,
          'total_price' => $item->total_price,
          'toppings_text' => $item->toppings->map(fn($t) => "{$t->topping_name} x{$t->quantity}")->join(', ')
        ];
      })->toArray()
    ];

    return [
      'type' => 'provisional',
      'metadata' => $metadata
    ];
  }

  /**
   * Lấy data in tất cả cho invoice (hóa đơn + tem + phiếu bếp)
   */
  public function getInvoiceAllData(int $invoiceId): array
  {
    $invoice = Invoice::with([
      'staff',
      'customer',
      'items.product',
      'items.toppings'
    ])->findOrFail($invoiceId);

    // Cập nhật print count cho invoice
    $invoice->markAsPrinted();

    // Tạo kitchen ticket nếu chưa có
    $kitchenTicket = $this->createOrGetKitchenTicket($invoice);

    // Tạo print labels cho từng item
    $printLabels = $this->createOrGetPrintLabels($invoice);

    return [
      'invoice' => [
        'id' => $invoice->id,
        'code' => $invoice->code,
        'type' => 'invoice',
        'metadata' => $invoice->toArray()
      ],
      'kitchen_ticket' => [
        'id' => $kitchenTicket->id,
        'type' => 'kitchen',
        'metadata' => $kitchenTicket->metadata
      ],
      'print_labels' => collect($printLabels)->map(fn($label) => [
        'id' => $label->id,
        'type' => 'label',
        'metadata' => $label->toArray()
      ])->toArray()
    ];
  }

  /**
   * Lấy data cho tem phiếu specific
   */
  public function getLabelData(int $labelId): array
  {
    $printLabel = PrintLabel::with('invoiceItem.product')->findOrFail($labelId);

    // Cập nhật print count
    $printLabel->markAsPrinted();

    return [
      'type' => 'label',
      'metadata' => [
        'id' => $printLabel->id,
        'product_code' => $printLabel->product_code,
        'product_name' => $printLabel->invoiceItem->product_name,
        'toppings_text' => $printLabel->toppings_text,
        'quantity' => $printLabel->invoiceItem->quantity,
        'print_count' => $printLabel->print_count,
        'last_printed_at' => $printLabel->last_printed_at
      ]
    ];
  }

  /**
   * Lấy data cho phiếu bếp specific  
   */
  public function getKitchenData(int $kitchenId): array
  {
    $kitchenTicket = KitchenTicket::with('invoice')->findOrFail($kitchenId);

    // Cập nhật print count
    $kitchenTicket->markAsPrinted();

    return [
      'type' => 'kitchen',
      'metadata' => [
        'id' => $kitchenTicket->id,
        'invoice_code' => $kitchenTicket->metadata['invoice_code'] ?? '',
        'table_name' => $kitchenTicket->metadata['table_name'] ?? '',
        'items' => $kitchenTicket->metadata['items'] ?? [],
        'print_count' => $kitchenTicket->print_count,
        'last_printed_at' => $kitchenTicket->last_printed_at
      ]
    ];
  }

  /**
   * Tạo hoặc lấy kitchen ticket cho invoice
   */
  protected function createOrGetKitchenTicket(Invoice $invoice): KitchenTicket
  {
    return KitchenTicket::firstOrCreate(
      ['invoice_id' => $invoice->id],
      [
        'branch_id' => $invoice->branch_id,
        'metadata' => [
          'invoice_code' => $invoice->code,
          'table_name' => $invoice->table?->name,
          'items' => $invoice->items->map(function ($item) {
            return [
              'product_name' => $item->product_name,
              'quantity' => $item->quantity,
              'toppings_text' => $item->toppings->map(fn($t) => "{$t->topping_name} x{$t->quantity}")->join(', ')
            ];
          })->toArray()
        ]
      ]
    );
  }

  /**
   * Tạo hoặc lấy print labels cho invoice
   */
  protected function createOrGetPrintLabels(Invoice $invoice): array
  {
    $printLabels = [];

    foreach ($invoice->items as $item) {
      $printLabel = PrintLabel::firstOrCreate(
        ['invoice_item_id' => $item->id],
        [
          'branch_id' => $invoice->branch_id,
          'product_code' => $item->product->code ?? $item->product_name,
          'toppings_text' => $item->toppings->map(fn($t) => "{$t->topping_name} x{$t->quantity}")->join(', ')
        ]
      );
      $printLabels[] = $printLabel;
    }

    return $printLabels;
  }

  /**
   * Lấy thông tin in cho invoice (dùng trong InvoiceController)
   */
  public function getInvoicePrintData(int $invoiceId): array
  {
    return $this->getInvoiceAllData($invoiceId);
  }
}
