<?php

namespace App\Http\Print\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Print\Requests\PrintProvisionalRequest;
use App\Http\Print\Requests\PrintInvoiceRequest;
use App\Http\Print\Requests\PrintLabelsRequest;
use App\Http\Print\Requests\PrintKitchenRequest;
use App\Http\Print\Requests\PrintAutoRequest;
use App\Http\Print\Resources\PrintJobResource;
use App\Http\Print\Resources\PrintQueueResource;
use App\Models\Order;
use App\Models\PrintQueue;
use App\Services\PrintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrintController extends Controller
{
  protected PrintService $printService;

  public function __construct(PrintService $printService)
  {
    $this->printService = $printService;
  }

  /**
   * In phiếu tạm tính
   * POST /api/print/provisional
   */
  public function provisional(PrintProvisionalRequest $request): JsonResponse
  {
    try {
      $order = Order::findOrFail($request->validated('order_id'));
      $result = $this->printService->printProvisional($order, $request->validated('device_id'));

      return response()->json([
        'success' => $result['success'],
        'message' => $result['message'],
        'data' => $result['success'] ? new PrintJobResource($result['data']) : null
      ], $result['success'] ? 200 : 400);
    } catch (\Exception $e) {
      Log::error('Print provisional failed', [
        'order_id' => $request->validated('order_id'),
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi in phiếu tạm tính: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * In hóa đơn chính thức
   * POST /api/print/invoice
   */
  public function invoice(PrintInvoiceRequest $request): JsonResponse
  {
    try {
      $order = Order::findOrFail($request->validated('order_id'));
      $result = $this->printService->printInvoice($order, $request->validated('device_id'));

      return response()->json([
        'success' => $result['success'],
        'message' => $result['message'],
        'data' => $result['success'] ? new PrintJobResource($result['data']) : null
      ], $result['success'] ? 200 : 400);
    } catch (\Exception $e) {
      Log::error('Print invoice failed', [
        'order_id' => $request->validated('order_id'),
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi in hóa đơn: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * In tem phiếu sản phẩm
   * POST /api/print/labels
   */
  public function labels(PrintLabelsRequest $request): JsonResponse
  {
    try {
      $order = Order::findOrFail($request->validated('order_id'));
      $result = $this->printService->printLabels($order, $request->validated('device_id'));

      return response()->json([
        'success' => $result['success'],
        'message' => $result['message'],
        'data' => $result['success'] ? PrintJobResource::collection($result['data']) : null
      ], $result['success'] ? 200 : 400);
    } catch (\Exception $e) {
      Log::error('Print labels failed', [
        'order_id' => $request->validated('order_id'),
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi in tem phiếu: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * In phiếu bếp
   * POST /api/print/kitchen
   */
  public function kitchen(PrintKitchenRequest $request): JsonResponse
  {
    try {
      $order = Order::findOrFail($request->validated('order_id'));
      $result = $this->printService->printKitchen($order, $request->validated('device_id'));

      return response()->json([
        'success' => $result['success'],
        'message' => $result['message'],
        'data' => $result['success'] ? PrintJobResource::collection($result['data']) : null
      ], $result['success'] ? 200 : 400);
    } catch (\Exception $e) {
      Log::error('Print kitchen failed', [
        'order_id' => $request->validated('order_id'),
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi in phiếu bếp: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * In tự động (phiếu bếp + tem phiếu)
   * POST /api/print/auto
   */
  public function autoPrint(PrintAutoRequest $request): JsonResponse
  {
    try {
      $order = Order::findOrFail($request->validated('order_id'));
      $result = $this->printService->autoPrint($order, $request->validated('device_id'));

      return response()->json([
        'success' => $result['success'],
        'message' => $result['message'],
        'results' => $result['results'] ?? null
      ], $result['success'] ? 200 : 400);
    } catch (\Exception $e) {
      Log::error('Auto print failed', [
        'order_id' => $request->validated('order_id'),
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi in tự động: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Lấy hàng đợi in
   * GET /api/print/queue
   */
  public function getQueue(Request $request): JsonResponse
  {
    $request->validate([
      'device_id' => 'nullable|string',
      'status' => 'nullable|in:pending,processing,completed,failed',
      'limit' => 'nullable|integer|min:1|max:100'
    ]);

    $query = PrintQueue::query();

    if ($request->filled('device_id')) {
      $query->where('device_id', $request->device_id);
    }

    if ($request->filled('status')) {
      $query->where('status', $request->status);
    }

    $jobs = $query->orderBy('priority', 'desc')
      ->orderBy('created_at', 'asc')
      ->limit($request->get('limit', 10))
      ->get();

    return response()->json([
      'success' => true,
      'data' => PrintQueueResource::collection($jobs)
    ]);
  }

  /**
   * Cập nhật trạng thái job in
   * PUT /api/print/queue/{id}/status
   */
  public function updateStatus(Request $request, int $id): JsonResponse
  {
    $request->validate([
      'status' => 'required|in:processing,completed,failed',
      'error_message' => 'nullable|string'
    ]);

    try {
      $job = PrintQueue::findOrFail($id);
      $job->update([
        'status' => $request->status,
        'error_message' => $request->error_message,
        'processed_at' => now()
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Cập nhật trạng thái thành công',
        'data' => new PrintQueueResource($job)
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Lỗi cập nhật trạng thái: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Xóa job in khỏi hàng đợi
   * DELETE /api/print/queue/{id}
   */
  public function deleteJob(int $id): JsonResponse
  {
    try {
      $job = PrintQueue::findOrFail($id);
      $job->delete();

      return response()->json([
        'success' => true,
        'message' => 'Xóa job in thành công'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Lỗi xóa job in: ' . $e->getMessage()
      ], 500);
    }
  }

  // ===== CLIENT APIs (No Authentication) =====

  /**
   * Lấy hàng đợi in cho client
   * GET /api/print/client/queue
   */
  public function getClientQueue(Request $request): JsonResponse
  {
    $request->validate([
      'device_id' => 'required|string',
      'limit' => 'nullable|integer|min:1|max:50'
    ]);

    $jobs = PrintQueue::where('device_id', $request->device_id)
      ->where('status', 'pending')
      ->orderBy('priority', 'desc')
      ->orderBy('created_at', 'asc')
      ->limit($request->get('limit', 10))
      ->get();

    return response()->json([
      'success' => true,
      'data' => PrintQueueResource::collection($jobs),
      'device_id' => $request->device_id,
      'timestamp' => now()->toISOString()
    ]);
  }

  /**
   * Cập nhật trạng thái từ client
   * PUT /api/print/client/queue/{id}/status
   */
  public function updateClientStatus(Request $request, int $id): JsonResponse
  {
    $request->validate([
      'status' => 'required|in:processing,completed,failed',
      'device_id' => 'required|string',
      'error_message' => 'nullable|string'
    ]);

    try {
      $job = PrintQueue::where('id', $id)
        ->where('device_id', $request->device_id)
        ->firstOrFail();

      $job->update([
        'status' => $request->status,
        'error_message' => $request->error_message,
        'processed_at' => now()
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Cập nhật trạng thái thành công'
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Lỗi cập nhật trạng thái: ' . $e->getMessage()
      ], 400);
    }
  }

  /**
   * Đăng ký thiết bị
   * POST /api/print/client/register
   */
  public function registerDevice(Request $request): JsonResponse
  {
    $request->validate([
      'device_id' => 'required|string|max:255',
      'device_name' => 'required|string|max:255',
      'device_type' => 'required|string|in:kitchen,receipt,label,cashier',
      'branch_id' => 'required|integer|exists:branches,id'
    ]);

    // Đây sẽ là logic đăng ký device trong Print Service thật
    return response()->json([
      'success' => true,
      'message' => 'Đăng ký thiết bị thành công',
      'device_id' => $request->device_id,
      'registered_at' => now()->toISOString()
    ]);
  }

  /**
   * Heartbeat từ thiết bị
   * PUT /api/print/client/heartbeat
   */
  public function deviceHeartbeat(Request $request): JsonResponse
  {
    $request->validate([
      'device_id' => 'required|string',
      'status' => 'required|string|in:online,offline,busy,error'
    ]);

    // Cập nhật trạng thái thiết bị
    return response()->json([
      'success' => true,
      'message' => 'Heartbeat received',
      'server_time' => now()->toISOString()
    ]);
  }

  /**
   * Lấy lịch sử in
   * GET /api/print/client/history
   */
  public function getHistory(Request $request): JsonResponse
  {
    $request->validate([
      'device_id' => 'nullable|string',
      'date_from' => 'nullable|date',
      'date_to' => 'nullable|date',
      'limit' => 'nullable|integer|min:1|max:100'
    ]);

    $query = PrintQueue::with('order');

    if ($request->filled('device_id')) {
      $query->where('device_id', $request->device_id);
    }

    if ($request->filled('date_from')) {
      $query->whereDate('created_at', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
      $query->whereDate('created_at', '<=', $request->date_to);
    }

    $history = $query->orderBy('created_at', 'desc')
      ->limit($request->get('limit', 50))
      ->get();

    return response()->json([
      'success' => true,
      'data' => PrintQueueResource::collection($history),
      'filters' => $request->only(['device_id', 'date_from', 'date_to'])
    ]);
  }

  /**
   * Lấy báo cáo hàng ngày
   * GET /api/print/client/history/daily
   */
  public function getDailyHistory(Request $request): JsonResponse
  {
    $request->validate([
      'date' => 'nullable|date',
      'device_id' => 'nullable|string'
    ]);

    $date = $request->get('date', now()->format('Y-m-d'));

    $query = PrintQueue::whereDate('created_at', $date);

    if ($request->filled('device_id')) {
      $query->where('device_id', $request->device_id);
    }

    $stats = [
      'total_jobs' => $query->count(),
      'completed' => $query->where('status', 'completed')->count(),
      'failed' => $query->where('status', 'failed')->count(),
      'pending' => $query->where('status', 'pending')->count(),
      'by_type' => $query->select('type', \DB::raw('count(*) as count'))
        ->groupBy('type')
        ->pluck('count', 'type'),
    ];

    return response()->json([
      'success' => true,
      'date' => $date,
      'device_id' => $request->device_id,
      'stats' => $stats
    ]);
  }
}
