<?php

namespace App\Listeners;

use App\Events\OrderCompleted;
use App\Models\PrintQueue;
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

      // 1. Tạo job in hóa đơn (bắt buộc)
      $invoiceJob = PrintQueue::create([
        'order_id' => $order->id,
        'print_type' => 'invoice',
        'branch_id' => $order->branch_id,
        'status' => 'pending',
        'data' => [
          'order_code' => $order->code,
          'table_name' => $order->table?->name,
          'total_amount' => $order->total_price,
          'payment_method' => $order->payment_method
        ]
      ]);
      $printJobs[] = [
        'type' => 'invoice',
        'job_id' => $invoiceJob->id
      ];

      // 2. Tạo job in phiếu bếp (nếu có món cần vào bếp)
      $kitchenItems = $order->items()->whereHas('product', function ($query) {
        $query->where('needs_kitchen', true);
      })->get();

      if ($kitchenItems->isNotEmpty()) {
        $kitchenJob = PrintQueue::create([
          'order_id' => $order->id,
          'print_type' => 'kitchen',
          'branch_id' => $order->branch_id,
          'status' => 'pending',
          'data' => [
            'order_code' => $order->code,
            'table_name' => $order->table?->name,
            'items_count' => $kitchenItems->count()
          ]
        ]);
        $printJobs[] = [
          'type' => 'kitchen',
          'job_id' => $kitchenJob->id
        ];
      }

      // 3. Tạo job in tem phiếu (nếu có món)
      $labelItems = $order->items()->where('quantity', '>', 0)->get();

      if ($labelItems->isNotEmpty()) {
        $labelsJob = PrintQueue::create([
          'order_id' => $order->id,
          'print_type' => 'labels',
          'branch_id' => $order->branch_id,
          'status' => 'pending',
          'data' => [
            'order_code' => $order->code,
            'table_name' => $order->table?->name,
            'labels_count' => $labelItems->sum('quantity')
          ]
        ]);
        $printJobs[] = [
          'type' => 'labels',
          'job_id' => $labelsJob->id
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
