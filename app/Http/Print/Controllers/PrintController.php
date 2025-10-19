<?php

namespace App\Http\Print\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Print\Requests\PrintProvisionalRequest;
use App\Http\Print\Requests\PrintInvoiceRequest;
use App\Http\Print\Requests\PrintLabelsRequest;
use App\Http\Print\Requests\PrintKitchenRequest;
use App\Http\Print\Requests\PrintAutoRequest;
use App\Http\Print\Requests\TestPrintRequest;
use App\Http\Print\Resources\PrintJobResource;
use App\Http\Print\Resources\PrintQueueResource;
use App\Models\Order;
use App\Models\PrintQueue;
use App\Models\PrintTemplate;
use App\Services\PrintService;
use App\Services\MockDataService;
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
      $result = $this->printService->printProvisional($order);

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
      $result = $this->printService->printInvoice($order);

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
      $result = $this->printService->printLabels($order);

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
      $result = $this->printService->printKitchen($order);

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

  /**
   * In thử với dữ liệu giả
   * POST /api/print/test
   */
  public function testPrint(TestPrintRequest $request): JsonResponse
  {
    try {
      $mockDataService = new MockDataService();
      $printType = $request->validated('print_type');
      $mockDataType = $request->validated('mock_data_type', 'simple');
      $templateId = $request->validated('template_id');

      // Generate mock order data
      $mockOrder = $mockDataService->generateMockOrder($mockDataType);

      // Lấy template nếu có chỉ định
      $template = null;
      if ($templateId) {
        $template = PrintTemplate::where('id', $templateId)
          ->where('type', $printType)
          ->first();

        if (!$template) {
          return response()->json([
            'success' => false,
            'message' => 'Template không tồn tại hoặc không phù hợp với loại in'
          ], 400);
        }
      }

      // Xử lý theo từng loại in
      $result = $this->processTestPrint($printType, $mockOrder, $deviceId, $template, $mockDataService);

      return response()->json([
        'success' => $result['success'],
        'message' => $result['message'],
        'data' => $result['data'] ?? null,
        'mock_data_preview' => $this->getMockDataPreview($printType, $mockOrder, $mockDataService)
      ], $result['success'] ? 200 : 400);
    } catch (\Exception $e) {
      Log::error('Test print failed', [
        'print_type' => $request->validated('print_type'),
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi in thử: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Xử lý test print theo từng loại
   */
  private function processTestPrint(string $printType, array $mockOrder, string $deviceId, ?PrintTemplate $template, MockDataService $mockDataService): array
  {
    switch ($printType) {
      case 'provisional':
        return $this->createTestPrintJob('provisional', $mockOrder, $deviceId, $template);

      case 'invoice':
        // Đảm bảo order đã thanh toán cho invoice
        $mockOrder['payment_status'] = 'paid';
        $mockOrder['paid_at'] = now();
        return $this->createTestPrintJob('invoice', $mockOrder, $deviceId, $template);

      case 'kitchen':
        $kitchenData = $mockDataService->generateKitchenTicketData($mockOrder);
        return $this->createTestPrintJob('kitchen', $kitchenData, $deviceId, $template);

      case 'labels':
        $labelData = $mockDataService->generateLabelData($mockOrder);
        return $this->createMultipleTestPrintJobs('labels', $labelData, $deviceId, $template);

      default:
        return [
          'success' => false,
          'message' => 'Loại in không được hỗ trợ'
        ];
    }
  }

  /**
   * Tạo một print job test
   */
  private function createTestPrintJob(string $type, array $data, string $deviceId, ?PrintTemplate $template): array
  {
    // Render content từ template hoặc sử dụng default
    $content = $this->renderTestContent($type, $data, $template);

    $printJob = PrintQueue::create([
      'order_id' => null, // Null cho test jobs
      'type' => $type,
      'content' => $content,
      'device_id' => $deviceId,
      'priority' => 'low',
      'status' => 'pending',
      'metadata' => [
        'is_test' => true,
        'test_data_type' => $data['mock_data_type'] ?? 'simple',
        'template_id' => $template?->id
      ]
    ]);

    return [
      'success' => true,
      'message' => "Tạo job in thử {$type} thành công",
      'data' => new PrintJobResource($printJob)
    ];
  }

  /**
   * Tạo nhiều print jobs (cho labels)
   */
  private function createMultipleTestPrintJobs(string $type, array $labelData, string $deviceId, ?PrintTemplate $template): array
  {
    $jobs = [];

    foreach ($labelData as $index => $label) {
      $content = $this->renderTestContent($type, $label, $template);

      $printJob = PrintQueue::create([
        'order_id' => null,
        'type' => $type,
        'content' => $content,
        'device_id' => $deviceId,
        'priority' => 'low',
        'status' => 'pending',
        'metadata' => [
          'is_test' => true,
          'label_index' => $index + 1,
          'total_labels' => count($labelData),
          'template_id' => $template?->id
        ]
      ]);

      $jobs[] = $printJob;
    }

    return [
      'success' => true,
      'message' => "Tạo " . count($jobs) . " job in tem thử thành công",
      'data' => PrintJobResource::collection($jobs)
    ];
  }

  /**
   * Render nội dung test từ template hoặc default
   */
  private function renderTestContent(string $type, array $data, ?PrintTemplate $template): string
  {
    if ($template) {
      // Sử dụng template được chỉ định
      $content = $template->content;

      // Replace variables trong template với mock data
      foreach ($data as $key => $value) {
        if (is_string($value) || is_numeric($value)) {
          $content = str_replace("{{" . $key . "}}", $value, $content);
        }
      }

      return $content;
    }

    // Sử dụng default template đơn giản
    return $this->getDefaultTestTemplate($type, $data);
  }

  /**
   * Default templates cho test print
   */
  private function getDefaultTestTemplate(string $type, array $data): string
  {
    switch ($type) {
      case 'provisional':
      case 'invoice':
        return $this->getReceiptTemplate($data, $type === 'invoice');

      case 'kitchen':
        return $this->getKitchenTemplate($data);

      case 'labels':
        return $this->getLabelTemplate($data);

      default:
        return "<html><body><h1>Test Print - {$type}</h1><pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre></body></html>";
    }
  }

  /**
   * Template cho receipt/invoice
   */
  private function getReceiptTemplate(array $data, bool $isInvoice = false): string
  {
    $title = $isInvoice ? 'HÓA ĐƠN THANH TOÁN' : 'PHIẾU TẠM TÍNH';
    $items = '';

    foreach ($data['items'] as $item) {
      $toppings = '';
      if (!empty($item['toppings'])) {
        foreach ($item['toppings'] as $topping) {
          $toppings .= "   + {$topping['name']}: " . number_format($topping['price']) . "đ\n";
        }
      }

      $items .= sprintf(
        "%s x%d\n%s   %s\n\n",
        $item['product_name'],
        $item['quantity'],
        $toppings,
        number_format($item['total']) . 'đ'
      );
    }

    return "
========================================
         {$data['branch_name']}
========================================
{$title}
----------------------------------------
Mã đơn: {$data['order_code']}
Bàn: {$data['table_name']}
Thời gian: " . $data['created_at']->format('d/m/Y H:i') . "
NV: {$data['staff_name']}
----------------------------------------
{$items}
----------------------------------------
Tạm tính: " . number_format($data['subtotal']) . "đ
Giảm giá: " . number_format($data['discount_amount']) . "đ
TỔNG CỘNG: " . number_format($data['total_amount']) . "đ
========================================
        ** ĐÂY LÀ IN THỬ **
========================================
";
  }

  /**
   * Template cho kitchen ticket
   */
  private function getKitchenTemplate(array $data): string
  {
    $items = '';
    foreach ($data['items'] as $item) {
      $items .= sprintf(
        "- %s x%d\n  %s\n\n",
        $item['product_name'],
        $item['quantity'],
        $item['note'] ?: '(Không có ghi chú)'
      );
    }

    return "
========================================
           PHIẾU BẾP TEST
========================================
Đơn: {$data['order_code']}
Bàn: {$data['table_name']}
Thời gian: " . $data['created_at']->format('H:i d/m') . "
========================================
{$items}
----------------------------------------
Ghi chú: {$data['notes']}
Ưu tiên: {$data['priority']}
========================================
        ** ĐÂY LÀ IN THỬ **
========================================
";
  }

  /**
   * Template cho label
   */
  private function getLabelTemplate(array $data): string
  {
    $toppings = '';
    if (!empty($data['toppings'])) {
      foreach ($data['toppings'] as $topping) {
        $toppings .= "+ {$topping['name']}\n";
      }
    }

    return "
====================
    TEM SẢN PHẨM
====================
{$data['product_name']}
Bàn: {$data['table_name']}
Đơn: {$data['order_code']}
--------------------
{$toppings}
{$data['note']}
--------------------
{$data['item_number']}/{$data['total_quantity']}
====================
   ** IN THỬ **
====================
";
  }

  /**
   * Lấy preview data cho response
   */
  private function getMockDataPreview(string $printType, array $mockOrder, MockDataService $mockDataService): array
  {
    switch ($printType) {
      case 'kitchen':
        return $mockDataService->generateKitchenTicketData($mockOrder);
      case 'labels':
        return array_slice($mockDataService->generateLabelData($mockOrder), 0, 3); // Chỉ show 3 labels đầu
      default:
        return [
          'order_code' => $mockOrder['order_code'],
          'table_name' => $mockOrder['table_name'],
          'items_count' => $mockOrder['items_count'],
          'total_amount' => $mockOrder['total_amount']
        ];
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
      'by_type' => $query->select('type', DB::raw('count(*) as count'))
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

  /**
   * Lấy danh sách chi nhánh để chọn
   * GET /api/print/branches
   */
  public function getBranches(): JsonResponse
  {
    try {
      $branches = \App\Models\Branch::where('is_active', true)
        ->select('id', 'name', 'address', 'phone')
        ->orderBy('name')
        ->get();

      return response()->json([
        'success' => true,
        'message' => 'Branches retrieved successfully',
        'data' => $branches
      ]);
    } catch (\Exception $e) {
      Log::error('Get branches failed', ['error' => $e->getMessage()]);

      return response()->json([
        'success' => false,
        'message' => 'Failed to get branches'
      ], 500);
    }
  }

  /**
   * Chọn chi nhánh làm việc
   * POST /api/print/branch/select
   */
  public function selectBranch(Request $request): JsonResponse
  {
    $request->validate([
      'branch_id' => 'required|exists:branches,id'
    ]);

    try {
      $branch = \App\Models\Branch::findOrFail($request->branch_id);

      if (!$branch->is_active) {
        return response()->json([
          'success' => false,
          'message' => 'Branch is not active'
        ], 403);
      }

      return response()->json([
        'success' => true,
        'message' => 'Branch selected successfully',
        'data' => [
          'branch_id' => $branch->id,
          'branch_name' => $branch->name,
          'address' => $branch->address,
          'phone' => $branch->phone
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Select branch failed', [
        'branch_id' => $request->branch_id,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Failed to select branch'
      ], 500);
    }
  }

  /**
   * Lấy cài đặt ứng dụng print
   * GET /api/print/settings
   */
  public function getSettings(Request $request): JsonResponse
  {
    try {
      $branchId = $request->current_branch_id;

      $settings = [
        'branch_id' => $branchId,
        'branch_name' => $request->current_branch->name,
        'auto_print_enabled' => true,
        'print_preview_enabled' => true,
        'default_templates' => [
          'provisional' => PrintTemplate::where('type', 'provisional')
            ->where('branch_id', $branchId)
            ->where('is_default', true)
            ->first()?->id,
          'invoice' => PrintTemplate::where('type', 'invoice')
            ->where('branch_id', $branchId)
            ->where('is_default', true)
            ->first()?->id,
          'kitchen' => PrintTemplate::where('type', 'kitchen')
            ->where('branch_id', $branchId)
            ->where('is_default', true)
            ->first()?->id,
          'labels' => PrintTemplate::where('type', 'labels')
            ->where('branch_id', $branchId)
            ->where('is_default', true)
            ->first()?->id,
        ],
        'available_devices' => [
          'receipt_printer_001' => 'Receipt Printer 001',
          'kitchen_printer_001' => 'Kitchen Printer 001',
          'label_printer_001' => 'Label Printer 001'
        ]
      ];

      return response()->json([
        'success' => true,
        'message' => 'Settings retrieved successfully',
        'data' => $settings
      ]);
    } catch (\Exception $e) {
      Log::error('Get settings failed', ['error' => $e->getMessage()]);

      return response()->json([
        'success' => false,
        'message' => 'Failed to get settings'
      ], 500);
    }
  }

  /**
   * Cập nhật cài đặt ứng dụng print
   * POST /api/print/settings
   */
  public function updateSettings(Request $request): JsonResponse
  {
    $request->validate([
      'auto_print_enabled' => 'boolean',
      'print_preview_enabled' => 'boolean',
      'default_templates' => 'array',
      'default_templates.provisional' => 'nullable|exists:print_templates,id',
      'default_templates.invoice' => 'nullable|exists:print_templates,id',
      'default_templates.kitchen' => 'nullable|exists:print_templates,id',
      'default_templates.labels' => 'nullable|exists:print_templates,id'
    ]);

    try {
      $branchId = $request->current_branch_id;

      // Cập nhật default templates nếu có
      if ($request->has('default_templates')) {
        foreach ($request->default_templates as $type => $templateId) {
          if ($templateId) {
            // Reset tất cả templates của type này về không default
            PrintTemplate::where('type', $type)
              ->where('branch_id', $branchId)
              ->update(['is_default' => false]);

            // Set template mới làm default
            PrintTemplate::where('id', $templateId)
              ->where('branch_id', $branchId)
              ->update(['is_default' => true]);
          }
        }
      }

      return response()->json([
        'success' => true,
        'message' => 'Settings updated successfully'
      ]);
    } catch (\Exception $e) {
      Log::error('Update settings failed', [
        'settings' => $request->all(),
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Failed to update settings'
      ], 500);
    }
  }
}
