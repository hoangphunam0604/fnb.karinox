<?php

namespace App\Services;

use App\Http\Print\Resources\InvoicePrintResource;
use App\Http\Print\Resources\KitchenPrintResource;
use App\Http\Print\Resources\LabelPrintResource;
use App\Models\Invoice;
use App\Models\PrintHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PrintHistoryService extends BaseService
{
  protected function model(): Model
  {
    return new PrintHistory();
  }

  public function confirmPrint(PrintHistory $printHistory): void
  {
    $printHistory->markAsPrinted();
  }

  public function markPrintFailed(PrintHistory $printHistory): void
  {
    $printHistory->markAsFailed();
  }

  protected function applySearch($query, array $params)
  {
    if (!empty($params['type'])) {
      $query->where('type', $params['type']);
    }
    return $query;
  }

  /**
   * Tạo tất cả print jobs cho invoice
   * Return array of targets với metadata đầy đủ
   */
  public function createPrintJobsForInvoice(Invoice $invoice): array
  {
    // Load các relationships cần thiết (nếu chưa load)
    if (!$invoice->relationLoaded('staff')) {
      $invoice->load([
        'staff',
        'customer',
        'items.product',
        'items.toppings'
      ]);
    }

    $targets = [];

    try {
      // 1. Target cho HÓA ĐƠN
      $invoiceMetadata = (new InvoicePrintResource($invoice))->resolve();
      $invoicePrint = $this->createPrintHistory($invoice, 'invoice', $invoiceMetadata);
      
      $targets[] = [
        'id' => $invoicePrint->id,
        'type' => 'invoice',
        'priority' => 1,
        'printer_type' => 'receipt',
        'metadata' => $invoiceMetadata
      ];

      // 2. Target cho PHIẾU BẾP (nếu có món cần in)
      $kitchenItems = $invoice->items->filter(fn($item) => $item->product && $item->product->print_kitchen === true);
      
      if ($kitchenItems->isNotEmpty()) {
        $kitchenMetadata = (new KitchenPrintResource($invoice))->resolve();
        $kitchenPrint = $this->createPrintHistory($invoice, 'kitchen', $kitchenMetadata);
        
        $targets[] = [
          'id' => $kitchenPrint->id,
          'type' => 'kitchen',
          'priority' => 2,
          'printer_type' => 'kitchen',
          'metadata' => $kitchenMetadata
        ];
      }

      // 3. Targets cho TEM PHIẾU (từng sản phẩm)
      $labelItems = $invoice->items->filter(fn($item) => $item->product && $item->product->print_label === true);
      
      foreach ($labelItems as $item) {
        $labelMetadata = (new LabelPrintResource($item, $invoice))->resolve();
        $labelPrint = $this->createPrintHistory($invoice, 'label', $labelMetadata);
        
        $targets[] = [
          'id' => $labelPrint->id,
          'type' => 'label',
          'priority' => 3,
          'printer_type' => 'label',
          'metadata' => $labelMetadata
        ];
      }

      Log::info('Print targets created for invoice', [
        'invoice_id' => $invoice->id,
        'invoice_code' => $invoice->code,
        'targets_count' => count($targets)
      ]);

      return [
        'invoice_id' => $invoice->id,
        'invoice_code' => $invoice->code,
        'branch_id' => $invoice->branch_id,
        'targets' => $targets
      ];
      
    } catch (\Exception $exception) {
      Log::error('Failed to create print targets for invoice', [
        'invoice_id' => $invoice->id,
        'invoice_code' => $invoice->code,
        'error' => $exception->getMessage()
      ]);

      throw $exception;
    }
  }

  /**
   * Helper method: Tạo print history record
   * KHÔNG broadcast - để controller/caller quyết định
   */
  private function createPrintHistory(Invoice $invoice, string $type, array $metadata): PrintHistory
  {
    return PrintHistory::create([
      'branch_id' => $invoice->branch_id,
      'type' => $type,
      'metadata' => $metadata,
      'status' => 'requested',
      'requested_at' => now()
    ]);
  }
}
