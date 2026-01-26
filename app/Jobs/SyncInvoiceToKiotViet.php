<?php

namespace App\Jobs;

use App\Client\KiotViet;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncInvoiceToKiotViet implements ShouldQueue
{
  use Queueable;

  protected Invoice $invoice;

  /**
   * Create a new job instance.
   */
  public function __construct(Invoice $invoice)
  {
    $this->invoice = $invoice;
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    try {
      // Load relationships needed
      $this->invoice->load(['items.product', 'branch', 'customer']);

      // Kiểm tra branch có kiotviet_id không
      if (!$this->invoice->branch || !$this->invoice->branch->kiotviet_id) {
        Log::warning("Invoice {$this->invoice->code}: Branch không có kiotviet_id");
        return;
      }

      // Chuẩn bị dữ liệu cho KiotViet
      $orderData = $this->prepareOrderData();

      // Gọi API KiotViet
      $kiotViet = new KiotViet();
      $response = $kiotViet->createOrder($orderData);

      // Lưu kết quả vào database
      $this->invoice->update([
        'kiotviet_synced' => true,
        'kiotviet_synced_at' => now(),
        'kiotviet_invoice_response' => json_encode($response)
      ]);

      Log::info("Invoice {$this->invoice->code} đã đồng bộ thành công lên KiotViet", [
        'kiotviet_order_id' => $response['id'] ?? null
      ]);
    } catch (Exception $e) {
      Log::error("Lỗi đồng bộ Invoice {$this->invoice->code} lên KiotViet: " . $e->getMessage(), [
        'exception' => $e
      ]);

      // Lưu lỗi vào database
      $this->invoice->update([
        'kiotviet_invoice_response' => json_encode([
          'error' => true,
          'message' => $e->getMessage(),
          'time' => now()->toDateTimeString()
        ])
      ]);

      // Re-throw exception để job có thể retry
      throw $e;
    }
  }

  /**
   * Chuẩn bị dữ liệu order theo format của KiotViet
   */
  protected function prepareOrderData(): array
  {
    $orderDetails = [];

    foreach ($this->invoice->items as $item) {
      // Chỉ đồng bộ những item có product_id và product có kiotviet_id
      if ($item->product_id && $item->product && $item->product->kiotviet_id) {
        $orderDetails[] = [
          'productId' => (int) $item->product->kiotviet_id,
          'quantity' => (float) $item->quantity,
          'price' => (float) $item->sale_price,
          'note' => $item->note ?? ''
        ];
      }
    }

    return [
      'branchId' => (int) $this->invoice->branch->kiotviet_id,
      'customerId' => $this->invoice->customer && $this->invoice->customer->kiotviet_id
        ? (int) $this->invoice->customer->kiotviet_id
        : null,
      'orderDetails' => $orderDetails
    ];
  }
}
