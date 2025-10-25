<?php

namespace App\Http\Print\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Print\Responses\PrintApiResponse;
use App\Services\PrintDataService;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrintDataController extends Controller
{
  public function __construct(private PrintDataService $printDataService) {}

  /**
   * Lấy data in theo type và id
   * GET /api/print/data/{type}/{id}
   * 
   * Types:
   * - provisional: in tạm tính với id order
   * - invoice-all: in tất cả hóa đơn, tem phiếu, phiếu bếp theo id invoice
   * - label: in tem phiếu với id tem phiếu  
   * - kitchen: in phiếu bếp với id phiếu bếp
   */
  public function getData(string $type, int $id): JsonResponse
  {
    try {
      $data = match ($type) {
        'provisional' => $this->printDataService->getProvisionalData($id),
        'invoice-all' => $this->printDataService->getInvoiceAllData($id),
        'label' => $this->printDataService->getLabelData($id),
        'kitchen' => $this->printDataService->getKitchenData($id),
        default => throw new \InvalidArgumentException('Type không hợp lệ: ' . $type)
      };

      return PrintApiResponse::success('Lấy dữ liệu in thành công', $data);
    } catch (\Exception $e) {
      return PrintApiResponse::error('Lỗi lấy dữ liệu in: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Lấy danh sách hóa đơn đã được yêu cầu in cho chi nhánh
   * GET /api/print/invoices/print-requested?branch_id=1
   */
  public function getPrintRequestedInvoices(Request $request): JsonResponse
  {
    try {
      $branchId = $request->query('branch_id');

      if (!$branchId) {
        return PrintApiResponse::error('branch_id là bắt buộc', 400);
      }

      $invoices = Invoice::printRequested()
        ->where('branch_id', $branchId)
        ->orderBy('print_requested_at', 'desc')
        ->select(['id', 'code', 'customer_name', 'total_price', 'print_requested_at', 'print_count', 'last_printed_at'])
        ->paginate(50);

      return PrintApiResponse::success('Lấy danh sách hóa đơn đã yêu cầu in thành công', [
        'invoices' => $invoices->items(),
        'pagination' => [
          'current_page' => $invoices->currentPage(),
          'last_page' => $invoices->lastPage(),
          'per_page' => $invoices->perPage(),
          'total' => $invoices->total()
        ]
      ]);
    } catch (\Exception $e) {
      return PrintApiResponse::error('Lỗi lấy danh sách hóa đơn: ' . $e->getMessage(), 500);
    }
  }
}
