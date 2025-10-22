<?php

namespace App\Http\Controllers\Print;

use App\Http\Controllers\Controller;
use App\Models\PrintTemplate;
use App\Models\Branch;
use App\Services\PrintTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PrintTemplateController for Print Management App
 * 
 * Provides templates for print management application to choose
 * appropriate templates for different print scenarios
 */
class PrintTemplateController extends Controller
{
  protected PrintTemplateService $service;

  public function __construct(PrintTemplateService $service)
  {
    $this->service = $service;
  }

  /**
   * Get available print templates for branch
   * GET /api/print/templates?connection_code=BRANCH001&type=invoice
   */
  public function index(Request $request): JsonResponse
  {
    $request->validate([
      'connection_code' => 'required|string|exists:branches,print_connection_code',
      'type' => 'nullable|string|in:provisional,invoice,labels,kitchen',
      'is_active' => 'nullable|boolean'
    ]);

    try {
      // Get branch by connection code
      $branch = Branch::where('print_connection_code', $request->connection_code)->first();

      if (!$branch) {
        return response()->json([
          'success' => false,
          'message' => 'Mã kết nối không hợp lệ'
        ], 400);
      }

      // Get templates for this branch
      $templates = $this->service->getUsedTemplateInBranch(
        $branch->id,
        $request->get('type'),
        $request->get('is_active', true)
      );

      // Transform to simple format for print app
      $templateList = $templates->map(function ($template) {
        return [
          'id' => $template->id,
          'name' => $template->name,
          'type' => $template->type,
          'is_default' => $template->is_default,
          'description' => $template->description,
          'created_at' => $template->created_at->format('Y-m-d H:i:s')
        ];
      });

      return response()->json([
        'success' => true,
        'data' => [
          'branch' => [
            'id' => $branch->id,
            'name' => $branch->name,
            'connection_code' => $branch->print_connection_code
          ],
          'templates' => $templateList
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Get templates for print app failed', [
        'connection_code' => $request->connection_code,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi khi lấy danh sách template: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Get template details by ID
   * GET /api/print/templates/{id}?connection_code=BRANCH001
   */
  public function show(Request $request, int $id): JsonResponse
  {
    $request->validate([
      'connection_code' => 'required|string|exists:branches,print_connection_code'
    ]);

    try {
      // Verify branch access
      $branch = Branch::where('print_connection_code', $request->connection_code)->first();

      if (!$branch) {
        return response()->json([
          'success' => false,
          'message' => 'Mã kết nối không hợp lệ'
        ], 400);
      }

      // Get template
      $template = PrintTemplate::where('id', $id)
        ->where(function ($query) use ($branch) {
          $query->where('branch_id', $branch->id)
            ->orWhereNull('branch_id'); // Global templates
        })
        ->where('is_active', true)
        ->first();

      if (!$template) {
        return response()->json([
          'success' => false,
          'message' => 'Template không tồn tại hoặc không có quyền truy cập'
        ], 404);
      }

      return response()->json([
        'success' => true,
        'data' => [
          'id' => $template->id,
          'name' => $template->name,
          'type' => $template->type,
          'description' => $template->description,
          'content' => $template->content,
          'is_default' => $template->is_default,
          'settings' => $template->settings ?? [],
          'created_at' => $template->created_at->format('Y-m-d H:i:s'),
          'updated_at' => $template->updated_at->format('Y-m-d H:i:s')
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Get template details for print app failed', [
        'template_id' => $id,
        'connection_code' => $request->connection_code,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi khi lấy chi tiết template: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Get default template for a specific type
   * GET /api/print/templates/default?connection_code=BRANCH001&type=invoice
   */
  public function getDefault(Request $request): JsonResponse
  {
    $request->validate([
      'connection_code' => 'required|string|exists:branches,print_connection_code',
      'type' => 'required|string|in:provisional,invoice,labels,kitchen'
    ]);

    try {
      // Get branch by connection code
      $branch = Branch::where('print_connection_code', $request->connection_code)->first();

      if (!$branch) {
        return response()->json([
          'success' => false,
          'message' => 'Mã kết nối không hợp lệ'
        ], 400);
      }

      // Try to get branch-specific default first
      $template = PrintTemplate::where('type', $request->type)
        ->where('branch_id', $branch->id)
        ->where('is_active', true)
        ->where('is_default', true)
        ->first();

      // If no branch-specific default, get global default
      if (!$template) {
        $template = PrintTemplate::where('type', $request->type)
          ->whereNull('branch_id')
          ->where('is_active', true)
          ->where('is_default', true)
          ->first();
      }

      if (!$template) {
        return response()->json([
          'success' => false,
          'message' => 'Không tìm thấy template mặc định cho loại này'
        ], 404);
      }

      return response()->json([
        'success' => true,
        'data' => [
          'id' => $template->id,
          'name' => $template->name,
          'type' => $template->type,
          'description' => $template->description,
          'content' => $template->content,
          'is_default' => $template->is_default,
          'settings' => $template->settings ?? [],
          'created_at' => $template->created_at->format('Y-m-d H:i:s'),
          'updated_at' => $template->updated_at->format('Y-m-d H:i:s')
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Get default template for print app failed', [
        'connection_code' => $request->connection_code,
        'type' => $request->type,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi khi lấy template mặc định: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Get template types available for branch
   * GET /api/print/templates/types?connection_code=BRANCH001
   */
  public function getTypes(Request $request): JsonResponse
  {
    $request->validate([
      'connection_code' => 'required|string|exists:branches,print_connection_code'
    ]);

    try {
      // Get branch by connection code
      $branch = Branch::where('print_connection_code', $request->connection_code)->first();

      if (!$branch) {
        return response()->json([
          'success' => false,
          'message' => 'Mã kết nối không hợp lệ'
        ], 400);
      }

      // Get available types for this branch
      $types = PrintTemplate::where(function ($query) use ($branch) {
        $query->where('branch_id', $branch->id)
          ->orWhereNull('branch_id');
      })
        ->where('is_active', true)
        ->distinct()
        ->pluck('type')
        ->map(function ($type) {
          return [
            'type' => $type,
            'label' => $this->getTypeLabel($type)
          ];
        });

      return response()->json([
        'success' => true,
        'data' => [
          'branch' => [
            'id' => $branch->id,
            'name' => $branch->name,
            'connection_code' => $branch->print_connection_code
          ],
          'types' => $types->values()
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Get template types for print app failed', [
        'connection_code' => $request->connection_code,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Lỗi khi lấy danh sách loại template: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Get human-readable label for template type
   */
  private function getTypeLabel(string $type): string
  {
    $labels = [
      'provisional' => 'Hóa đơn tạm tính',
      'invoice' => 'Hóa đơn thanh toán',
      'labels' => 'Tem/Nhãn',
      'kitchen' => 'Phiếu bếp'
    ];

    return $labels[$type] ?? $type;
  }
}
