<?php

namespace App\Http\Print\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\Print\Resources\PrintRequestResource;
use App\Http\Print\Responses\PrintApiResponse;
use App\Services\PrintDataService;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrintDataController extends Controller
{
  public function __construct(private PrintDataService $printDataService, private InvoiceService $invoiceService) {}

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
        'labels' => $this->printDataService->getLabelsDataFromInvoice($id),
        'order-kitchen' => $this->printDataService->getKitchentDataFromOrder($id),
        'invoice-kitchen' => $this->printDataService->getKitchentDataFromInvoice($id),
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
    $branchId = $request->query('branch_id');

    if (!$branchId) {
      return PrintApiResponse::error('branch_id là bắt buộc', 400);
    }
    $invoices = $this->invoiceService->getPrintRequestedInvoices($branchId);
    return PrintApiResponse::success('Lấy dữ liệu in thành công', PrintRequestResource::collection($invoices));
  }
}
