<?php

namespace App\Http\Print\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Print\Responses\PrintApiResponse;
use App\Models\Invoice;
use App\Services\PrintHistoryService;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
  public function __construct(private PrintHistoryService $printHistoryService) {}

  /**
   * Lấy thông tin in cho invoice
   * Frontend gọi API này sau khi nhận WebSocket notification
   */
  public function getPrintData(int $invoiceId): JsonResponse
  {
    // Tìm invoice
    $invoice = Invoice::with([
      'staff',
      'customer',
      'items.product',
      'items.toppings'
    ])->findOrFail($invoiceId);

    // Tạo print history và lấy targets
    $printData = $this->printHistoryService->createPrintJobsForInvoice($invoice);

    return PrintApiResponse::success(
      'Lấy thông tin in thành công',
      $printData
    );
  }
}
