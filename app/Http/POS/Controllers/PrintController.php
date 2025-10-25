<?php

namespace App\Http\POS\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PrintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Events\PrintRequested;
use App\Services\OrderService;
use App\Services\InvoiceService;
use App\Enums\PaymentStatus;

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
      $order = $this->orderService->findOrderById($orderId);  // Broadcast event đến frontend qua WebSocket
      $event = new PrintRequested('order', $order->id, $order->branch_id);
      broadcast($event);

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
      $invoice = $this->invoiceService->findById($invoiceId);
      // Kiểm tra order đã thanh toán chưa
      if ($invoice->payment_status !== PaymentStatus::PAID) {
        return response()->json([
          'success' => false,
          'message' => 'Đơn hàng chưa được thanh toán'
        ], 400);
      }
      $event = new PrintRequested('invoice', $invoice->id, $invoice->branch_id);
      broadcast($event);

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
