<?php

namespace App\Http\Print\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Print\Requests\CreatePrintTemplateRequest;
use App\Http\Print\Requests\UpdatePrintTemplateRequest;
use App\Http\Print\Resources\PrintTemplateResource;
use App\Models\PrintTemplate;
use App\Services\PrintTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PrintTemplateController extends Controller
{
  protected PrintTemplateService $service;

  public function __construct(PrintTemplateService $service)
  {
    $this->service = $service;
  }

  /**
   * Lấy danh sách print templates
   * GET /api/print/templates
   */
  public function index(Request $request): JsonResponse
  {
    $request->validate([
      'type' => 'nullable|string|in:provisional,invoice,labels,kitchen',
      'is_active' => 'nullable|boolean',
      'branch_id' => 'nullable|integer|exists:branches,id'
    ]);

    try {
      $branchId = $request->get('branch_id')
        ?? (app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null);

      if (!$branchId) {
        return response()->json([
          'success' => false,
          'message' => 'Vui lòng chọn chi nhánh'
        ], 400);
      }

      $templates = $this->service->getUsedTemplateInBranch(
        $branchId,
        $request->get('type'),
        $request->get('is_active', true)
      );

      return response()->json([
        'success' => true,
        'data' => PrintTemplateResource::collection($templates),
        'branch_id' => $branchId
      ]);
    } catch (\Exception $e) {
      Log::error('Get print templates failed', [
        'error' => $e->getMessage(),
        'branch_id' => $branchId ?? null
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi khi lấy danh sách template: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Lấy chi tiết template
   * GET /api/print/templates/{id}
   */
  public function show(int $id): JsonResponse
  {
    try {
      $template = PrintTemplate::findOrFail($id);

      return response()->json([
        'success' => true,
        'data' => new PrintTemplateResource($template)
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Template không tồn tại'
      ], 404);
    }
  }

  /**
   * Tạo template mới
   * POST /api/print/templates
   */
  public function store(CreatePrintTemplateRequest $request): JsonResponse
  {
    try {
      $data = $request->validated();

      // Thêm branch_id nếu không có
      if (!isset($data['branch_id'])) {
        $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null;
        if ($branchId) {
          $data['branch_id'] = $branchId;
        }
      }

      $template = $this->service->create($data);

      return response()->json([
        'success' => true,
        'message' => 'Tạo template thành công',
        'data' => new PrintTemplateResource($template)
      ], 201);
    } catch (\Exception $e) {
      Log::error('Create print template failed', [
        'data' => $request->validated(),
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi tạo template: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Cập nhật template
   * PUT /api/print/templates/{id}
   */
  public function update(UpdatePrintTemplateRequest $request, int $id): JsonResponse
  {
    try {
      $template = PrintTemplate::findOrFail($id);
      $data = $request->validated();

      $updatedTemplate = $this->service->update($template, $data);

      return response()->json([
        'success' => true,
        'message' => 'Cập nhật template thành công',
        'data' => new PrintTemplateResource($updatedTemplate)
      ]);
    } catch (\Exception $e) {
      Log::error('Update print template failed', [
        'template_id' => $id,
        'data' => $request->validated(),
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi cập nhật template: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Xóa template
   * DELETE /api/print/templates/{id}
   */
  public function destroy(int $id): JsonResponse
  {
    try {
      $template = PrintTemplate::findOrFail($id);

      // Kiểm tra xem template có đang được sử dụng không
      if ($this->isTemplateInUse($template)) {
        return response()->json([
          'success' => false,
          'message' => 'Không thể xóa template đang được sử dụng'
        ], 400);
      }

      $this->service->delete($template);

      return response()->json([
        'success' => true,
        'message' => 'Xóa template thành công'
      ]);
    } catch (\Exception $e) {
      Log::error('Delete print template failed', [
        'template_id' => $id,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi xóa template: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Duplicate template
   * POST /api/print/templates/{id}/duplicate
   */
  public function duplicate(int $id): JsonResponse
  {
    try {
      $originalTemplate = PrintTemplate::findOrFail($id);

      $data = $originalTemplate->toArray();
      unset($data['id'], $data['created_at'], $data['updated_at']);

      // Thêm suffix để phân biệt
      $data['name'] = $data['name'] . ' (Copy)';
      $data['is_default'] = false; // Copy không được là default

      $newTemplate = $this->service->create($data);

      return response()->json([
        'success' => true,
        'message' => 'Sao chép template thành công',
        'data' => new PrintTemplateResource($newTemplate)
      ], 201);
    } catch (\Exception $e) {
      Log::error('Duplicate print template failed', [
        'template_id' => $id,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi sao chép template: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Set template làm mặc định
   * POST /api/print/templates/{id}/set-default
   */
  public function setDefault(int $id): JsonResponse
  {
    try {
      $template = PrintTemplate::findOrFail($id);

      // Reset tất cả template cùng loại về không default
      PrintTemplate::where('type', $template->type)
        ->where('branch_id', $template->branch_id)
        ->update(['is_default' => false]);

      // Set template này làm default
      $template->update(['is_default' => true]);

      return response()->json([
        'success' => true,
        'message' => 'Đặt template mặc định thành công',
        'data' => new PrintTemplateResource($template->fresh())
      ]);
    } catch (\Exception $e) {
      Log::error('Set default template failed', [
        'template_id' => $id,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi đặt template mặc định: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Preview template với mock data
   * POST /api/print/templates/{id}/preview
   */
  public function preview(Request $request, int $id): JsonResponse
  {
    $request->validate([
      'mock_data_type' => 'nullable|string|in:simple,complex,with_toppings,large_order'
    ]);

    try {
      $template = PrintTemplate::findOrFail($id);

      // Generate mock data theo loại template
      $mockDataService = new \App\Services\MockDataService();
      $mockOrder = $mockDataService->generateMockOrder(
        $request->get('mock_data_type', 'simple')
      );

      // Render content với mock data
      $content = $this->renderTemplateWithMockData($template, $mockOrder);

      return response()->json([
        'success' => true,
        'data' => [
          'template' => new PrintTemplateResource($template),
          'rendered_content' => $content,
          'mock_data_used' => $request->get('mock_data_type', 'simple')
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Preview template failed', [
        'template_id' => $id,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi xem trước template: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Kiểm tra template có đang được sử dụng không
   */
  private function isTemplateInUse(PrintTemplate $template): bool
  {
    // Kiểm tra trong print_queues
    $inQueue = \App\Models\PrintQueue::whereJsonContains('metadata->template_id', $template->id)
      ->exists();

    // Kiểm tra nếu là default template
    $isDefault = $template->is_default;

    return $inQueue || $isDefault;
  }

  /**
   * Render template với mock data
   */
  private function renderTemplateWithMockData(PrintTemplate $template, array $mockData): string
  {
    $content = $template->content;

    // Replace basic variables
    $variables = [
      '{{order_code}}' => $mockData['order_code'] ?? 'TEST-001',
      '{{table_name}}' => $mockData['table_name'] ?? 'Bàn Test',
      '{{branch_name}}' => $mockData['branch_name'] ?? 'Chi nhánh Test',
      '{{total_amount}}' => number_format($mockData['total_amount'] ?? 0),
      '{{subtotal}}' => number_format($mockData['subtotal'] ?? 0),
      '{{discount_amount}}' => number_format($mockData['discount_amount'] ?? 0),
      '{{created_at}}' => now()->format('d/m/Y H:i'),
      '{{staff_name}}' => $mockData['staff_name'] ?? 'Nhân viên Test'
    ];

    // Replace variables
    foreach ($variables as $key => $value) {
      $content = str_replace($key, $value, $content);
    }

    // Handle items loop nếu có
    if (strpos($content, '{{#items}}') !== false && isset($mockData['items'])) {
      $content = $this->renderItemsLoop($content, $mockData['items']);
    }

    return $content;
  }

  /**
   * Render items loop trong template
   */
  private function renderItemsLoop(string $content, array $items): string
  {
    $pattern = '/{{#items}}(.*?){{\/items}}/s';

    return preg_replace_callback($pattern, function ($matches) use ($items) {
      $itemTemplate = $matches[1];
      $renderedItems = '';

      foreach ($items as $item) {
        $itemContent = $itemTemplate;
        $itemContent = str_replace('{{product_name}}', $item['product_name'] ?? '', $itemContent);
        $itemContent = str_replace('{{quantity}}', $item['quantity'] ?? 0, $itemContent);
        $itemContent = str_replace('{{price}}', number_format($item['price'] ?? 0), $itemContent);
        $itemContent = str_replace('{{total}}', number_format($item['total'] ?? 0), $itemContent);
        $itemContent = str_replace('{{note}}', $item['note'] ?? '', $itemContent);

        $renderedItems .= $itemContent;
      }

      return $renderedItems;
    }, $content);
  }
}
