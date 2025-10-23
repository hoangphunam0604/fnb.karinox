<?php

namespace App\Listeners;

use App\Events\OrderCompleted;
// PrintQueue removed - now using PrintService via Socket
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CreatePostPaymentPrintJobs implements ShouldQueue
{
  use InteractsWithQueue;

  /**
   * Handle the event.
   */
  public function handle(OrderCompleted $event): void
  {
    $order = $event->order;

    try {
      $printJobs = [];

      // 1. In hóa đơn qua Socket
      $invoicePrintId = \App\Services\PrintService::printInvoiceViaSocket($order);
      $printJobs[] = [
        'type' => 'invoice',
        'print_id' => $invoicePrintId
      ];

      // 2. Tạo job in phiếu bếp (nếu có món cần vào bếp)
      $kitchenItems = $order->items()->whereHas('product', function ($query) {
        $query->where('print_kitchen', true);
      })->get();

      if ($kitchenItems->isNotEmpty()) {
        // 2. In phiếu bếp qua Socket (phương pháp mới)
        $kitchenPrintId = \App\Services\PrintService::printKitchenViaSocket($order);

        $printJobs[] = [
          'type' => 'kitchen',
          'print_id' => $kitchenPrintId
        ];
      }

      // 3. In tem phiếu qua Socket (nếu có món)
      $labelItems = $order->items()->where('quantity', '>', 0)->get();

      if ($labelItems->isNotEmpty()) {
        // Thêm method printLabelsViaSocket vào PrintService
        $labelsPrintId = \App\Services\PrintService::printViaSocket([
          'type' => 'label',
          'metadata' => [
            'order_id' => $order->id,
            'order_code' => $order->code,
            'table_name' => $order->table?->name,
            'items' => $labelItems->map(function ($item) {
              return [
                'name' => $item->product->name,
                'quantity' => $item->quantity
              ];
            }),
            'labels_count' => $labelItems->sum('quantity')
          ]
        ], $order->branch_id);

        $printJobs[] = [
          'type' => 'labels',
          'print_id' => $labelsPrintId
        ];
      }

      Log::info('Auto print jobs created after order completion', [
        'order_id' => $order->id,
        'order_code' => $order->code,
        'payment_method' => $order->payment_method,
        'print_jobs' => $printJobs
      ]);
    } catch (\Exception $e) {
      Log::error('Failed to create auto print jobs after order completion', [
        'order_id' => $order->id,
        'order_code' => $order->code,
        'payment_method' => $order->payment_method,
        'error' => $e->getMessage()
      ]);

      // Re-throw để có thể retry nếu cần
      throw $e;
    }
  }

  /**
   * Handle a job failure.
   */
  public function failed(OrderCompleted $event, \Throwable $exception): void
  {
    Log::error('CreatePostPaymentPrintJobs listener failed', [
      'order_id' => $event->order->id,
      'order_code' => $event->order->code,
      'error' => $exception->getMessage()
    ]);
  }
}
