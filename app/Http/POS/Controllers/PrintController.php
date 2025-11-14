<?php

namespace App\Http\POS\Controllers;

use App\Http\Common\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Events\PrintRequested;
use App\Http\POS\Responses\ApiResponse;
use App\Services\OrderService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class PrintController extends Controller
{

  public function __construct(protected OrderService $orderService, protected InvoiceService $invoiceService) {}
  /**
   * Tạo job in tạm tính
   * POST /api/pos/orders/{id}/provisional
   */
  public function provisional($orderCode): JsonResponse
  {
    try {
      $order = $this->orderService->findByCode($orderCode);
      broadcast(new PrintRequested('provisional', ['id' => $order->id], $order->branch_id));
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
   * Gửi yêu cầu in phiếu bếp
   */
  public function kitchen($orderCode)
  {
    $order = $this->orderService->findByCode($orderCode);
    if ($order->kitchenItems->isEmpty()) {
      return ApiResponse::error('Đã báo rồi hoặc không có món nào cần báo bếp');
    }

    broadcast(new PrintRequested('order-kitchen', ['id' => $order->id], $order->branch_id));
  }

  /**
   * Tạo job in hóa đơn
   * POST /api/pos/orders/{invoiceCode}/invoice
   */
  public function invoice($invoiceCode): JsonResponse
  {
    try {
      $invoice = $this->invoiceService->findByCode($invoiceCode);
      $this->invoiceService->requestPrint($invoice->id, $invoice->branch_id);
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

  /**
   * Kê tiền
   */
  public function cashInventory(Request $request): JsonResponse
  {
    try {
      // Get branch ID using the karinox_branch_id binding or from query parameter
      $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : $request->query('branch_id');

      if (!$branchId) {
        return response()->json([
          'success' => false,
          'message' => 'Branch ID is required'
        ], 400);
      }

      $payload = $request->all();
      event(new PrintRequested('cash-inventory', $payload, $branchId));

      return response()->json([
        'success' => true,
        'message' => 'Cash inventory print request sent successfully',
        'data' => $payload
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'An error occurred while processing cash inventory',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
