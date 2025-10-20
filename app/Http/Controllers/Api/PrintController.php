<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PrintHistory;
use App\Services\PrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PrintController extends Controller
{
  // Constructor không cần injection vì dùng static methods

  /**
   * In phiếu tạm tính
   * POST /api/print/provisional
   */
  public function provisional(Request $request)
  {
    $request->validate([
      'order_id' => 'required|exists:orders,id',
      'device_id' => 'nullable|string'
    ]);

    $order = Order::findOrFail($request->order_id);
    $result = $this->printService->printProvisional($order, $request->device_id);

    return response()->json($result, $result['success'] ? 200 : 400);
  }

  /**
   * In hóa đơn chính thức
   * POST /api/print/invoice
   */
  public function invoice(Request $request)
  {
    $request->validate([
      'order_id' => 'required|exists:orders,id',
      'device_id' => 'nullable|string'
    ]);

    $order = Order::findOrFail($request->order_id);
    $result = $this->printService->printInvoice($order, $request->device_id);

    return response()->json($result, $result['success'] ? 200 : 400);
  }

  /**
   * In tem phiếu
   * POST /api/print/labels
   */
  public function labels(Request $request)
  {
    $request->validate([
      'order_id' => 'required|exists:orders,id',
      'item_ids' => 'nullable|array',
      'item_ids.*' => 'exists:order_items,id',
      'device_id' => 'nullable|string'
    ]);

    $order = Order::findOrFail($request->order_id);
    $result = $this->printService->printLabels($order, $request->item_ids, $request->device_id);

    return response()->json($result, $result['success'] ? 200 : 400);
  }

  /**
   * In phiếu bếp
   * POST /api/print/kitchen
   */
  public function kitchen(Request $request)
  {
    $request->validate([
      'order_id' => 'required|exists:orders,id',
      'item_ids' => 'nullable|array',
      'item_ids.*' => 'exists:order_items,id',
      'device_id' => 'nullable|string'
    ]);

    $order = Order::findOrFail($request->order_id);
    $result = $this->printService->printKitchenTickets($order, $request->item_ids, $request->device_id);

    return response()->json($result, $result['success'] ? 200 : 400);
  }

  /**
   * In tự động dựa trên cài đặt sản phẩm
   * POST /api/print/auto
   */
  public function autoPrint(Request $request)
  {
    $request->validate([
      'order_id' => 'required|exists:orders,id',
      'device_id' => 'nullable|string'
    ]);

    $order = Order::findOrFail($request->order_id);
    $results = $this->printService->autoPrint($order, $request->device_id);

    return response()->json([
      'success' => true,
      'results' => $results,
      'message' => 'Auto print completed'
    ]);
  }

  /**
   * Lấy hàng đợi in cho device
   * GET /api/print/queue
   */
  public function getQueue(Request $request)
  {
    $request->validate([
      'device_id' => 'required|string',
      'branch_id' => 'nullable|exists:branches,id',
      'limit' => 'nullable|integer|min:1|max:100'
    ]);

    $query = PrintQueue::pending()
      ->forDevice($request->device_id)
      ->byPriority();

    if ($request->branch_id) {
      $query->where('branch_id', $request->branch_id);
    }

    $jobs = $query->limit($request->limit ?? 10)->get();

    return response()->json([
      'success' => true,
      'jobs' => $jobs->map(function ($job) {
        return [
          'id' => $job->id,
          'type' => $job->type,
          'content' => $job->content,
          'priority' => $job->priority,
          'metadata' => $job->metadata,
          'created_at' => $job->created_at->toISOString()
        ];
      })
    ]);
  }

  /**
   * Đánh dấu job đã xử lý
   * POST /api/print/queue/{job}/processed
   */
  public function markProcessed(PrintQueue $job)
  {
    try {
      $job->markAsProcessed();

      Log::info("Print job marked as processed", [
        'job_id' => $job->id,
        'type' => $job->type
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Job marked as processed'
      ]);
    } catch (\Exception $e) {
      Log::error("Failed to mark job as processed: " . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Failed to mark job as processed'
      ], 500);
    }
  }

  /**
   * Đánh dấu job thất bại
   * POST /api/print/queue/{job}/failed
   */
  public function markFailed(PrintQueue $job, Request $request)
  {
    $request->validate([
      'error_message' => 'required|string'
    ]);

    try {
      $job->markAsFailed($request->error_message);

      Log::error("Print job marked as failed", [
        'job_id' => $job->id,
        'type' => $job->type,
        'error' => $request->error_message
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Job marked as failed'
      ]);
    } catch (\Exception $e) {
      Log::error("Failed to mark job as failed: " . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Failed to mark job as failed'
      ], 500);
    }
  }

  /**
   * Retry job thất bại
   * POST /api/print/queue/{job}/retry
   */
  public function retryJob(PrintQueue $job)
  {
    try {
      if ($job->retry()) {
        Log::info("Print job retried", [
          'job_id' => $job->id,
          'type' => $job->type,
          'retry_count' => $job->retry_count
        ]);

        return response()->json([
          'success' => true,
          'message' => 'Job retried successfully'
        ]);
      } else {
        return response()->json([
          'success' => false,
          'message' => 'Job cannot be retried (max retries reached)'
        ], 400);
      }
    } catch (\Exception $e) {
      Log::error("Failed to retry job: " . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Failed to retry job'
      ], 500);
    }
  }

  /**
   * Lấy trạng thái in của order
   * GET /api/print/order/{order}/status
   */
  public function getOrderPrintStatus(Order $order)
  {
    $items = $order->items()->with('product')->get();

    $status = [
      'order_id' => $order->id,
      'order_code' => $order->order_code,
      'printed_bill' => $order->printed_bill,
      'printed_bill_at' => $order->printed_bill_at,
      'items' => $items->map(function ($item) {
        return [
          'id' => $item->id,
          'product_name' => $item->product_name,
          'product_type' => $item->product->type,
          'print_label' => $item->print_label,
          'printed_label' => $item->printed_label,
          'printed_label_at' => $item->printed_label_at,
          'print_kitchen' => $item->print_kitchen,
          'printed_kitchen' => $item->printed_kitchen,
          'printed_kitchen_at' => $item->printed_kitchen_at
        ];
      }),
      'pending_jobs' => PrintQueue::pending()
        ->whereJsonContains('metadata->order_id', $order->id)
        ->count()
    ];

    return response()->json([
      'success' => true,
      'status' => $status
    ]);
  }

  /**
   * Preview nội dung in
   * GET /api/print/preview
   */
  public function preview(Request $request)
  {
    $request->validate([
      'order_id' => 'required|exists:orders,id',
      'type' => 'required|in:invoice,provisional,label,kitchen',
      'item_id' => 'nullable|exists:order_items,id'
    ]);

    try {
      $order = Order::with(['items.product', 'branch', 'staff'])->findOrFail($request->order_id);

      switch ($request->type) {
        case 'provisional':
          $result = $this->printService->printProvisional($order);
          break;
        case 'invoice':
          $result = $this->printService->printInvoice($order);
          break;
        case 'label':
          if ($request->item_id) {
            $result = $this->printService->printLabels($order, [$request->item_id]);
          } else {
            return response()->json(['error' => 'item_id required for label preview'], 400);
          }
          break;
        case 'kitchen':
          if ($request->item_id) {
            $result = $this->printService->printKitchenTickets($order, [$request->item_id]);
          } else {
            $result = $this->printService->printKitchenTickets($order);
          }
          break;
        default:
          return response()->json(['error' => 'Invalid print type'], 400);
      }

      return response()->json([
        'success' => true,
        'content' => $result['content'] ?? '',
        'preview' => true
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Preview failed: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Lấy trạng thái thiết bị in
   * GET /api/pos/print-client/device/status
   */
  public function getDeviceStatus(Request $request)
  {
    $deviceId = $request->get('authenticated_device_id') ?: $request->get('device_id');

    try {
      // Thống kê jobs cho device
      $stats = [
        'device_id' => $deviceId,
        'server_time' => now()->toISOString(),
        'queue_stats' => [
          'pending' => PrintQueue::pending()->forDevice($deviceId)->count(),
          'processing' => PrintQueue::where('status', 'processing')->forDevice($deviceId)->count(),
          'total_today' => PrintQueue::whereDate('created_at', today())->forDevice($deviceId)->count(),
          'success_today' => PrintQueue::whereDate('created_at', today())
            ->where('status', 'processed')
            ->forDevice($deviceId)->count(),
          'failed_today' => PrintQueue::whereDate('created_at', today())
            ->where('status', 'failed')
            ->forDevice($deviceId)->count(),
        ],
        'config' => [
          'poll_interval' => config('print.poll_interval', 5000),
          'max_jobs_per_request' => config('print.max_jobs_per_request', 10),
          'retry_max' => config('print.retry_max', 3),
        ],
        'last_activity' => PrintQueue::where('device_id', $deviceId)
          ->latest('updated_at')
          ->value('updated_at')
      ];

      Log::info("Device status requested", [
        'device_id' => $deviceId,
        'pending_jobs' => $stats['queue_stats']['pending']
      ]);

      return response()->json([
        'success' => true,
        'status' => 'online',
        'data' => $stats
      ]);
    } catch (\Exception $e) {
      Log::error("Failed to get device status: " . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Failed to get device status'
      ], 500);
    }
  }

  /**
   * ===============================================
   * PHƯƠNG PHÁP MỚI: EVENT-DRIVEN VIA SOCKET
   * ===============================================
   */

  /**
   * Frontend xác nhận đã in thành công
   * POST /api/print/confirm
   */
  public function confirmPrinted(Request $request)
  {
    $request->validate([
      'print_id' => 'required|string'
    ]);

    $success = PrintService::confirmPrinted($request->print_id);

    if ($success) {
      return response()->json([
        'success' => true,
        'message' => 'Print confirmed successfully'
      ]);
    }

    return response()->json([
      'success' => false,
      'message' => 'Print ID not found'
    ], 404);
  }

  /**
   * Frontend báo lỗi in
   * POST /api/print/error
   */
  public function reportError(Request $request)
  {
    $request->validate([
      'print_id' => 'required|string',
      'error' => 'required|string'
    ]);

    $success = PrintService::reportPrintError($request->print_id, $request->error);

    if ($success) {
      return response()->json([
        'success' => true,
        'message' => 'Error reported successfully'
      ]);
    }

    return response()->json([
      'success' => false,
      'message' => 'Print ID not found'
    ], 404);
  }

  /**
   * Lấy lịch sử in
   * GET /api/print/history
   */
  public function history(Request $request)
  {
    $query = \App\Models\PrintHistory::with(['branch'])
      ->byBranch($request->user()->current_branch_id ?? 1)
      ->latest('requested_at');

    // Filters
    if ($request->has('status')) {
      $query->byStatus($request->status);
    }

    if ($request->has('device_id')) {
      $query->byDevice($request->device_id);
    }

    if ($request->has('today') && $request->today) {
      $query->today();
    }

    $histories = $query->paginate(20);

    return response()->json([
      'success' => true,
      'data' => $histories
    ]);
  }

  /**
   * Thống kê in
   * GET /api/print/stats
   */
  public function stats(Request $request)
  {
    $branchId = $request->user()->current_branch_id ?? 1;

    $stats = [
      'today' => [
        'total' => \App\Models\PrintHistory::byBranch($branchId)->today()->count(),
        'printed' => \App\Models\PrintHistory::byBranch($branchId)->today()->byStatus('printed')->count(),
        'failed' => \App\Models\PrintHistory::byBranch($branchId)->today()->byStatus('failed')->count(),
        'pending' => \App\Models\PrintHistory::byBranch($branchId)->today()->byStatus('requested')->count()
      ],
      'by_type' => \App\Models\PrintHistory::byBranch($branchId)
        ->today()
        ->selectRaw('type, count(*) as count')
        ->groupBy('type')
        ->pluck('count', 'type'),
      'avg_duration' => \App\Models\PrintHistory::byBranch($branchId)
        ->today()
        ->byStatus('printed')
        ->avg('print_duration')
    ];

    return response()->json([
      'success' => true,
      'data' => $stats
    ]);
  }

  /**
   * Manual print request (từ admin)
   * POST /api/print/manual
   */
  public function manualPrint(Request $request)
  {
    $request->validate([
      'type' => 'required|in:invoice,kitchen,label,receipt',
      'order_id' => 'required|exists:orders,id',
      'device_id' => 'nullable|string'
    ]);

    $order = Order::findOrFail($request->order_id);
    $printId = null;

    switch ($request->type) {
      case 'invoice':
        $printId = PrintService::printInvoiceViaSocket($order, $request->device_id);
        break;
      case 'kitchen':
        $kitchenItems = $order->items()->whereHas('product', function ($query) {
          $query->where('print_kitchen', true);
        })->get();
        $printId = PrintService::printKitchenViaSocket($order, $kitchenItems, $request->device_id);
        break;
      case 'label':
        $printId = PrintService::printViaSocket([
          'type' => 'label',
          'content' => "Tem #{$order->code}",
          'metadata' => [
            'order_id' => $order->id,
            'order_code' => $order->code,
            'manual_print' => true
          ]
        ], $order->branch_id, $request->device_id);
        break;
    }

    return response()->json([
      'success' => true,
      'print_id' => $printId,
      'message' => 'Print job sent successfully'
    ]);
  }
}
