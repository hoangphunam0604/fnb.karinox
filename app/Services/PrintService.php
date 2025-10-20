<?php

namespace App\Services;

use App\Events\PrintRequested;
use App\Models\PrintHistory;
use Illuminate\Support\Facades\Log;

class PrintService
{
  /**
   * Gửi lệnh in đơn giản và hiệu quả qua WebSocket
   */
  public static function printViaSocket(array $printData, int $branchId, ?string $deviceId = null): string
  {
    Log::info('Print requested via socket', [
      'type' => $printData['type'],
      'branch_id' => $branchId,
      'device_id' => $deviceId
    ]);

    // Broadcast event đến frontend qua WebSocket
    $event = new PrintRequested($printData, $branchId, $deviceId);
    broadcast($event);

    return $event->printId;
  }

  /**
   * In hóa đơn qua Socket
   */
  public static function printInvoiceViaSocket($order, ?string $deviceId = null): string
  {
    return self::printViaSocket([
      'type' => 'invoice',
      'content' => "Hóa đơn #{$order->code}",
      'metadata' => [
        'order_id' => $order->id,
        'order_code' => $order->code,
        'table_name' => $order->table?->name,
        'total_amount' => $order->total_price,
        'payment_method' => $order->payment_method,
        'items' => $order->items->map(function ($item) {
          return [
            'name' => $item->product->name,
            'quantity' => $item->quantity,
            'price' => $item->unit_price,
            'total' => $item->total_price
          ];
        })
      ],
      'priority' => 'normal'
    ], $order->branch_id, $deviceId);
  }

  /**
   * In phiếu bếp qua Socket
   */
  public static function printKitchenViaSocket($order, ?string $deviceId = null): string
  {
    // Lấy items từ order - kiểm tra có relationship hay không
    $kitchenItems = collect();
    try {
      if ($order->relationLoaded('items')) {
        $kitchenItems = $order->items;
      } else {
        // Lazy load items nếu chưa load
        $kitchenItems = $order->items()->get();
      }
    } catch (\Exception $e) {
      // Nếu không có items, sử dụng empty collection
      $kitchenItems = collect();
    }

    return self::printViaSocket([
      'type' => 'kitchen',
      'content' => "Phiếu bếp #{$order->code}",
      'metadata' => [
        'order_id' => $order->id,
        'order_code' => $order->code,
        'table_name' => $order->table?->name ?? 'N/A',
        'items' => $kitchenItems->map(function ($item) {
          return [
            'name' => $item->product->name ?? 'Unknown',
            'quantity' => $item->quantity ?? 0,
            'note' => $item->note ?? ''
          ];
        }),
        'special_instructions' => $order->note ?? ''
      ],
      'priority' => 'high' // Bếp ưu tiên cao
    ], $order->branch_id, $deviceId);
  }

  /**
   * Xác nhận in thành công từ frontend
   */
  public static function confirmPrinted(string $printId): bool
  {
    $printHistory = PrintHistory::where('print_id', $printId)->first();

    if (!$printHistory) {
      Log::warning("Print history not found: {$printId}");
      return false;
    }

    $printHistory->markAsPrinted();

    Log::info("Print confirmed: {$printId}", [
      'type' => $printHistory->type,
      'duration' => $printHistory->print_duration
    ]);

    return true;
  }

  /**
   * Báo lỗi in từ frontend
   */
  public static function reportPrintError(string $printId, string $error): bool
  {
    $printHistory = PrintHistory::where('print_id', $printId)->first();

    if (!$printHistory) {
      Log::warning("Print history not found: {$printId}");
      return false;
    }

    $printHistory->markAsFailed($error);

    Log::error("Print failed: {$printId}", [
      'error' => $error,
      'type' => $printHistory->type
    ]);

    return true;
  }

  /**
   * In hóa đơn từ Invoice (data chính xác 100%)
   */
  public static function printInvoiceFromInvoice($invoice, ?string $deviceId = null): string
  {
    return self::printViaSocket([
      'type' => 'invoice',
      'content' => "HÓA ĐƠN CHÍNH THỨC #{$invoice->code}",
      'metadata' => [
        'invoice_id' => $invoice->id,
        'invoice_code' => $invoice->code,
        'order_id' => $invoice->order->id,
        'order_code' => $invoice->order->code,
        'table_name' => $invoice->order->table?->name,
        'total_amount' => $invoice->total_amount,
        'payment_method' => $invoice->order->payment_method,
        'printed_from' => 'invoice', // Flag để audit trail
        'items' => $invoice->order->items->map(function ($item) {
          return [
            'name' => $item->product->name,
            'quantity' => $item->quantity,
            'price' => $item->unit_price,
            'total' => $item->total_price
          ];
        })
      ]
    ], $invoice->order->branch_id, $deviceId);
  }
}
