<?php

namespace App\Http\POS\Controllers;

use App\Http\Common\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Events\PrintRequested;
use App\Services\OrderService;
use App\Services\InvoiceService;

class PrintController extends Controller
{
  protected $orderService;
  protected $invoiceService;

  public function __construct(OrderService $orderService, InvoiceService $invoiceService)
  {
    $this->orderService = $orderService;
    $this->invoiceService = $invoiceService;
  }
  /**
   * Tạo job in tạm tính
   * POST /api/pos/orders/{id}/provisional
   */
  public function provisional($orderId): JsonResponse
  {
    try {
      $this->orderService->requestPrintProvisional($orderId);
      return response()->json([
        'success' => true,
        'message' => 'Đã gửi lệnh in tạm tính'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Lỗi in tạm tính: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Tạo job in hóa đơn
   * POST /api/pos/orders/{id}/invoice
   */
  public function invoice($invoiceId): JsonResponse
  {
    try {
      $this->invoiceService->requestPrint($invoiceId);
      return response()->json([
        'success' => true,
        'message' => 'Đã gửi lệnh in hóa đơn'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Lỗi in hóa đơn: ' . $e->getMessage()
      ], 500);
    }
  }
}
