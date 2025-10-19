<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PrintQueue;
use Illuminate\Support\Facades\Log;

class PrintJobService
{
  /**
   * Tạo các print jobs sau khi thanh toán thành công
   */
  public function createPostPaymentJobs(Order $order, string $paymentMethod = 'unknown'): array
  {
    try {
      $printJobs = [];

      // 1. Tạo job in hóa đơn (bắt buộc)
      $invoiceJob = $this->createInvoiceJob($order, $paymentMethod);
      $printJobs[] = [
        'type' => 'invoice',
        'job_id' => $invoiceJob->id
      ];

      // 2. Tạo job in phiếu bếp (nếu có món cần vào bếp)
      $kitchenJob = $this->createKitchenJobIfNeeded($order);
      if ($kitchenJob) {
        $printJobs[] = [
          'type' => 'kitchen',
          'job_id' => $kitchenJob->id
        ];
      }

      // 3. Tạo job in tem phiếu (nếu có món)
      $labelsJob = $this->createLabelsJobIfNeeded($order);
      if ($labelsJob) {
        $printJobs[] = [
          'type' => 'labels',
          'job_id' => $labelsJob->id
        ];
      }

      Log::info('Auto print jobs created after payment', [
        'order_id' => $order->id,
        'order_code' => $order->code,
        'payment_method' => $paymentMethod,
        'print_jobs' => $printJobs
      ]);

      return $printJobs;
    } catch (\Exception $e) {
      Log::error('Failed to create auto print jobs after payment', [
        'order_id' => $order->id,
        'order_code' => $order->code,
        'payment_method' => $paymentMethod,
        'error' => $e->getMessage()
      ]);

      throw $e;
    }
  }

  /**
   * Tạo job in hóa đơn
   */
  public function createInvoiceJob(Order $order, string $paymentMethod = 'unknown'): PrintQueue
  {
    return PrintQueue::create([
      'order_id' => $order->id,
      'print_type' => 'invoice',
      'branch_id' => $order->branch_id,
      'status' => 'pending',
      'data' => [
        'order_code' => $order->code,
        'table_name' => $order->table?->name,
        'total_amount' => $order->total_price,
        'payment_method' => $paymentMethod,
        'customer_name' => $order->customer?->name,
        'items_count' => $order->items()->count()
      ]
    ]);
  }

  /**
   * Tạo job in phiếu bếp nếu cần
   */
  public function createKitchenJobIfNeeded(Order $order): ?PrintQueue
  {
    $kitchenItems = $order->items()->whereHas('product', function ($query) {
      $query->where('needs_kitchen', true);
    })->get();

    if ($kitchenItems->isEmpty()) {
      return null;
    }

    return PrintQueue::create([
      'order_id' => $order->id,
      'print_type' => 'kitchen',
      'branch_id' => $order->branch_id,
      'status' => 'pending',
      'data' => [
        'order_code' => $order->code,
        'table_name' => $order->table?->name,
        'items_count' => $kitchenItems->count(),
        'priority' => $this->getKitchenPriority($order),
        'notes' => $order->note
      ]
    ]);
  }

  /**
   * Tạo job in tem phiếu nếu cần
   */
  public function createLabelsJobIfNeeded(Order $order): ?PrintQueue
  {
    $labelItems = $order->items()->where('quantity', '>', 0)->get();

    if ($labelItems->isEmpty()) {
      return null;
    }

    return PrintQueue::create([
      'order_id' => $order->id,
      'print_type' => 'labels',
      'branch_id' => $order->branch_id,
      'status' => 'pending',
      'data' => [
        'order_code' => $order->code,
        'table_name' => $order->table?->name,
        'labels_count' => $labelItems->sum('quantity'),
        'items_detail' => $labelItems->map(function ($item) {
          return [
            'product_name' => $item->product->name,
            'quantity' => $item->quantity,
            'note' => $item->note
          ];
        })->toArray()
      ]
    ]);
  }

  /**
   * Tạo job in tạm tính
   */
  public function createProvisionalJob(Order $order): PrintQueue
  {
    return PrintQueue::create([
      'order_id' => $order->id,
      'print_type' => 'provisional',
      'branch_id' => $order->branch_id,
      'status' => 'pending',
      'data' => [
        'order_code' => $order->code,
        'table_name' => $order->table?->name,
        'subtotal' => $order->subtotal,
        'discount' => $order->discount_amount,
        'total_amount' => $order->total_price,
        'items_count' => $order->items()->count()
      ]
    ]);
  }

  /**
   * Tạo multiple jobs theo loại
   */
  public function createMultipleJobs(Order $order, array $printTypes): array
  {
    $jobs = [];

    foreach ($printTypes as $type) {
      $job = null;

      switch ($type) {
        case 'provisional':
          $job = $this->createProvisionalJob($order);
          break;
        case 'invoice':
          $job = $this->createInvoiceJob($order);
          break;
        case 'kitchen':
          $job = $this->createKitchenJobIfNeeded($order);
          break;
        case 'labels':
          $job = $this->createLabelsJobIfNeeded($order);
          break;
      }

      if ($job) {
        $jobs[] = [
          'type' => $type,
          'job_id' => $job->id
        ];
      }
    }

    return $jobs;
  }

  /**
   * Lấy trạng thái print jobs của order
   */
  public function getOrderPrintStatus(int $orderId): array
  {
    $printJobs = PrintQueue::where('order_id', $orderId)
      ->orderBy('created_at', 'desc')
      ->get();

    $status = [];
    foreach ($printJobs as $job) {
      $status[$job->print_type] = [
        'job_id' => $job->id,
        'status' => $job->status,
        'created_at' => $job->created_at,
        'updated_at' => $job->updated_at,
        'error_message' => $job->error_message
      ];
    }

    return $status;
  }

  /**
   * Xác định độ ưu tiên cho kitchen job
   */
  private function getKitchenPriority(Order $order): string
  {
    // Logic xác định priority dựa trên thời gian, loại món, etc.
    $totalItems = $order->items()->sum('quantity');

    if ($totalItems > 10) {
      return 'high';
    } elseif ($totalItems > 5) {
      return 'medium';
    }

    return 'normal';
  }
}
