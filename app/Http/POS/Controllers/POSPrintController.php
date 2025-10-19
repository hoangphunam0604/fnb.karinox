<?php

namespace App\Http\POS\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PrintJobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class POSPrintController extends Controller
{
  protected OrderService $orderService;
  protected PrintJobService $printJobService;

  public function __construct(OrderService $orderService, PrintJobService $printJobService)
  {
    $this->orderService = $orderService;
    $this->printJobService = $printJobService;
  }

  /**
   * Tạo job in tạm tính
   * POST /api/pos/orders/{id}/provisional
   */
  public function provisional($orderId): JsonResponse
  {
    try {
      $order = Order::findOrFail($orderId);
      $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : $order->branch_id;

      // Tạo print job vào queue
      $printJob = PrintQueue::create([
        'order_id' => $orderId,
        'print_type' => 'provisional',
        'branch_id' => $branchId,
        'status' => 'pending',
        'data' => [
          'order_code' => $order->code,
          'table_name' => $order->table?->name,
        ]
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Đã tạo job in tạm tính',
        'data' => [
          'job_id' => $printJob->id,
          'print_type' => 'provisional',
          'status' => 'pending'
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Create provisional print job failed', [
        'order_id' => $orderId,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi tạo job in tạm tính: ' . $e->getMessage()
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

      $printJob = PrintQueue::create([
        'order_id' => $orderId,
        'print_type' => 'invoice',
        'branch_id' => $branchId,
        'status' => 'pending',
        'data' => [
          'order_code' => $order->code,
          'table_name' => $order->table?->name,
          'total_amount' => $order->total_price
        ]
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Đã tạo job in hóa đơn',
        'data' => [
          'job_id' => $printJob->id,
          'print_type' => 'invoice',
          'status' => 'pending'
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Create invoice print job failed', [
        'order_id' => $orderId,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi tạo job in hóa đơn: ' . $e->getMessage()
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

      // Lấy items cần vào bếp
      $kitchenItems = $order->items()->whereHas('product', function ($query) {
        $query->where('needs_kitchen', true);
      })->get();

      if ($kitchenItems->isEmpty()) {
        return response()->json([
          'success' => false,
          'message' => 'Đơn hàng không có món cần vào bếp'
        ], 400);
      }

      $printJob = PrintQueue::create([
        'order_id' => $orderId,
        'print_type' => 'kitchen',
        'branch_id' => $branchId,
        'status' => 'pending',
        'data' => [
          'order_code' => $order->code,
          'table_name' => $order->table?->name,
          'items_count' => $kitchenItems->count()
        ]
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Đã tạo job in phiếu bếp',
        'data' => [
          'job_id' => $printJob->id,
          'print_type' => 'kitchen',
          'status' => 'pending',
          'items_count' => $kitchenItems->count()
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Create kitchen print job failed', [
        'order_id' => $orderId,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi tạo job in phiếu bếp: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Tạo job in tem phiếu
   * POST /api/pos/orders/{id}/labels
   */
  public function labels($orderId): JsonResponse
  {
    try {
      $order = Order::findOrFail($orderId);
      $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : $order->branch_id;

      // Lấy items cần in tem
      $labelItems = $order->items()->where('quantity', '>', 0)->get();

      if ($labelItems->isEmpty()) {
        return response()->json([
          'success' => false,
          'message' => 'Đơn hàng không có món cần in tem'
        ], 400);
      }

      $printJob = PrintQueue::create([
        'order_id' => $orderId,
        'print_type' => 'labels',
        'branch_id' => $branchId,
        'status' => 'pending',
        'data' => [
          'order_code' => $order->code,
          'table_name' => $order->table?->name,
          'labels_count' => $labelItems->sum('quantity')
        ]
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Đã tạo job in tem phiếu',
        'data' => [
          'job_id' => $printJob->id,
          'print_type' => 'labels',
          'status' => 'pending',
          'labels_count' => $labelItems->sum('quantity')
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Create labels print job failed', [
        'order_id' => $orderId,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi tạo job in tem phiếu: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Tự động in theo workflow
   * POST /api/pos/orders/{id}/auto-print
   */
  public function autoPrint($orderId): JsonResponse
  {
    try {
      $order = Order::findOrFail($orderId);

      $results = [];

      // In phiếu bếp nếu có món cần vào bếp
      $kitchenResult = $this->kitchen($orderId);
      if ($kitchenResult->getData()->success) {
        $results[] = 'kitchen';
      }

      // In tem phiếu nếu có món
      $labelsResult = $this->labels($orderId);
      if ($labelsResult->getData()->success) {
        $results[] = 'labels';
      }

      return response()->json([
        'success' => true,
        'message' => 'Đã tạo các job in tự động',
        'data' => [
          'printed_types' => $results,
          'order_code' => $order->code
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
   * Kiểm tra trạng thái in của đơn hàng
   * GET /api/pos/orders/{id}/print-status
   */
  public function getPrintStatus($orderId): JsonResponse
  {
    try {
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

      return response()->json([
        'success' => true,
        'data' => $status
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Lỗi kiểm tra trạng thái in: ' . $e->getMessage()
      ], 500);
    }
  }
}
