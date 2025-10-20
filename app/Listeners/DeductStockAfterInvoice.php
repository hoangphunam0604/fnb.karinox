<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Models\InventoryTransaction;
use App\Services\OrderService;
use App\Services\StockDeductionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeductStockAfterInvoice implements ShouldQueue
{
  use InteractsWithQueue;

  /**
   * Create the event listener.
   */
  public function __construct()
  {
    //
  }

  /**
   * Handle the event.
   */
  public function handle(InvoiceCreated $event): void
  {
    $invoice = $event->invoice;
    $order = $invoice->order;

    if (!$order) {
      Log::warning('Invoice has no associated order', ['invoice_id' => $invoice->id]);
      return;
    }

    // Chỉ trừ kho khi order đã completed và chưa trừ kho
    if ($order->order_status->value !== 'completed') {
      Log::info('Order is not completed, skipping stock deduction', [
        'order_id' => $order->id,
        'status' => $order->order_status->value
      ]);
      return;
    }

    // Check if stock has already been deducted (tránh trừ trung)
    if ($order->stock_deducted_at) {
      Log::info('Stock already deducted for order', [
        'order_id' => $order->id,
        'deducted_at' => $order->stock_deducted_at
      ]);
      return;
    }

    try {
      // Sử dụng StockDeductionService để xử lý trừ kho cho Invoice
      $stockDeductionService = app(StockDeductionService::class);
      $stockDeductionService->deductStockForCompletedInvoice($invoice);

      Log::info('Stock deducted successfully after invoice creation', [
        'order_id' => $order->id,
        'invoice_id' => $invoice->id
      ]);
    } catch (\Exception $e) {
      Log::error('Failed to deduct stock after invoice creation', [
        'invoice_id' => $event->invoice->id,
        'error' => $e->getMessage()
      ]);

      // Re-throw to trigger queue retry
      throw $e;
    }
  }
}
