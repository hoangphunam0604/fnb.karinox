<?php

namespace App\Http\POS\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PrintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PrintController extends Controller
{
  /**
   * Tạo job in tạm tính
   * POST /api/pos/orders/{id}/provisional
   */
  public function provisional($orderId): JsonResponse
  {
    try {
      $order = Order::findOrFail($orderId);
      $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : $order->branch_id;

      // Sử dụng WebSocket để in ngay lập tức
      $printId = PrintService::printViaSocket([
        'type' => 'other', // 'provisional' không có trong enum, dùng 'other'  
        'content' => "Tạm tính #{$order->code}",
        'metadata' => [
          'order_id' => $order->id,
          'order_code' => $order->code,
          'table_name' => $order->table?->name,
          'print_type' => 'provisional' // Lưu type thật vào metadata
        ]
      ], $branchId, 'pos-station');

      return response()->json([
        'success' => true,
        'message' => 'Đã gửi lệnh in tạm tính',
        'data' => [
          'print_id' => $printId,
          'print_type' => 'provisional',
          'status' => 'sent'
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Print provisional failed', [
        'order_id' => $orderId,
        'error' => $e->getMessage()
      ]);

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
  public function invoice($orderId): JsonResponse
  {
    try {
      $order = Order::findOrFail($orderId);
      $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : $order->branch_id;

      // Kiểm tra order đã thanh toán chưa
      if ($order->payment_status !== 'paid') {
        return response()->json([
          'success' => false,
          'message' => 'Đơn hàng chưa được thanh toán'
        ], 400);
      }

      $printId = PrintService::printInvoiceViaSocket($order, 'pos-station');

      return response()->json([
        'success' => true,
        'message' => 'Đã gửi lệnh in hóa đơn',
        'data' => [
          'print_id' => $printId,
          'print_type' => 'invoice',
          'status' => 'sent'
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Print invoice failed', [
        'order_id' => $orderId,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi in hóa đơn: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Tạo job in phiếu bếp
   * POST /api/pos/orders/{id}/kitchen
   */
  public function kitchen($orderId): JsonResponse
  {
    try {
      $order = Order::findOrFail($orderId);
      $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : $order->branch_id;

      $printId = PrintService::printKitchenViaSocket($order, 'kitchen-printer');

      return response()->json([
        'success' => true,
        'message' => 'Đã gửi lệnh in phiếu bếp',
        'data' => [
          'print_id' => $printId,
          'print_type' => 'kitchen',
          'status' => 'sent'
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Print kitchen ticket failed', [
        'order_id' => $orderId,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi in phiếu bếp: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Tạo job in nhãn
   * POST /api/pos/orders/{id}/labels
   */
  public function labels($orderId): JsonResponse
  {
    try {
      $order = Order::findOrFail($orderId);
      $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : $order->branch_id;

      $printId = PrintService::printViaSocket([
        'type' => 'label', // Đúng enum value
        'content' => "Nhãn #{$order->code}",
        'metadata' => [
          'order_id' => $order->id,
          'order_code' => $order->code,
        ]
      ], $branchId, 'label-printer');

      return response()->json([
        'success' => true,
        'message' => 'Đã gửi lệnh in nhãn',
        'data' => [
          'print_id' => $printId,
          'print_type' => 'labels',
          'status' => 'sent'
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Print labels failed', [
        'order_id' => $orderId,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi in nhãn: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * In tự động (invoice + kitchen ticket)
   * POST /api/pos/orders/{id}/auto-print
   */
  public function autoPrint($orderId): JsonResponse
  {
    try {
      $order = Order::findOrFail($orderId);
      $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : $order->branch_id;

      $printIds = [];

      // In hóa đơn nếu đã thanh toán
      if ($order->payment_status->value === 'paid') {
        $printIds['invoice'] = PrintService::printInvoiceViaSocket($order, 'pos-station');
      }

      // In phiếu bếp
      $printIds['kitchen'] = PrintService::printKitchenViaSocket($order, 'kitchen-printer');

      return response()->json([
        'success' => true,
        'message' => 'Đã gửi lệnh in tự động',
        'data' => [
          'print_ids' => $printIds,
          'status' => 'sent'
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Auto print failed', [
        'order_id' => $orderId,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi in tự động: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Lấy trạng thái in của đơn hàng
   * GET /api/pos/orders/{id}/print-status
   */
  public function getPrintStatus($orderId): JsonResponse
  {
    try {
      $printHistories = \App\Models\PrintHistory::whereJsonContains('metadata->order_id', $orderId)
        ->orderBy('created_at', 'desc')
        ->get()
        ->groupBy(function ($item) {
          // Lấy print_type từ metadata, fallback về type
          return $item->metadata['print_type'] ?? $item->type;
        });

      $status = [];
      foreach ($printHistories as $type => $histories) {
        $latest = $histories->first();
        $status[$type] = [
          'status' => $latest->status,
          'created_at' => $latest->created_at,
          'completed_at' => $latest->completed_at,
          'error_message' => $latest->error_message,
        ];
      }

      return response()->json([
        'success' => true,
        'data' => $status
      ]);
    } catch (\Exception $e) {
      Log::error('Get print status failed', [
        'order_id' => $orderId,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi lấy trạng thái in: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * In hóa đơn chính thức từ Invoice (đảm bảo data chính xác 100%)
   * POST /api/pos/invoices/{id}/print
   */
  public function printFromInvoice($invoiceId): JsonResponse
  {
    try {
      $invoice = \App\Models\Invoice::with(['order', 'order.items.product'])
        ->findOrFail($invoiceId);

      $order = $invoice->order;
      $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : $order->branch_id;

      // In từ Invoice data (chính xác 100%)
      $printId = PrintService::printInvoiceFromInvoice($invoice, 'pos-station');

      return response()->json([
        'success' => true,
        'message' => 'Đã gửi lệnh in hóa đơn từ Invoice',
        'data' => [
          'print_id' => $printId,
          'print_type' => 'invoice',
          'source' => 'invoice',
          'invoice_id' => $invoice->id,
          'status' => 'sent'
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Print from invoice failed', [
        'invoice_id' => $invoiceId,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi in từ Invoice: ' . $e->getMessage()
      ], 500);
    }
  }
}
