<?php

namespace App\Http\Print\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Print\Requests\ConnectRequest;
use App\Http\Print\Requests\ReportErrorRequest;
use App\Http\Print\Requests\PrintStatsRequest;
use App\Services\PrintManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PrintController extends Controller
{
  public function __construct(
    private PrintManagementService $printManagementService
  ) {}

  /**
   * Kết nối với chi nhánh qua connection code
   * POST /api/print/connect
   */
  public function connect(ConnectRequest $request): JsonResponse
  {
    $data = $this->printManagementService->connectToBranch(
      $request->validated('connection_code')
    );

    return response()->json(
      [
        'success' => true,
        'message' => 'Kết nối thành công',
        'data' => $data
      ]
    );
  }

  /**
   * Xác nhận đã in thành công
   * POST /api/print/confirm
   */
  public function confirm(ConfirmPrintRequest $request): JsonResponse
  {
    $data = $this->printManagementService->confirmPrint(
      $request->validated('print_id'),
      $request->validated('device_id'),
      $request->validated('status', 'printed')
    );
    return response()->json(
      [
        'success' => true,
        'message' => 'Xác nhận in thành công',
        'data' => $data
      ]
    );
  }

  /**
   * Báo lỗi print job
   * POST /api/print/error
   */
  public function reportError(ReportErrorRequest $request): JsonResponse
  {
    $this->printManagementService->reportPrintError(
      $request->validated('print_id'),
      $request->validated('device_id'),
      $request->validated('error_type'),
      $request->validated('error_message'),
      $request->validated('error_details')
    );

    return response()->json(
      [
        'success' => true,
        'message' => 'Đã báo lỗi print job'
      ]
    );
  }

  /**
   * Lấy lịch sử in
   * GET /api/print/history
   */
  public function history(PrintHistoryRequest $request): JsonResponse
  {
    try {
      $filters = $request->only(['device_id', 'branch_id', 'status', 'from_date', 'to_date']);
      $perPage = $request->validated('per_page', 20);

      $history = $this->printManagementService->getPrintHistory($filters, $perPage);

      return PrintApiResponse::success('Lấy lịch sử in thành công', $history);
    } catch (\Exception $e) {
      Log::error('Get print history failed', [
        'error' => $e->getMessage()
      ]);

      return PrintApiResponse::error('Lỗi lấy lịch sử in: ' . $e->getMessage());
    }
  }

  /**
   * Thống kê in ấn
   * GET /api/print/stats
   */
  public function stats(PrintStatsRequest $request): JsonResponse
  {
    try {
      $filters = $request->only(['branch_id', 'device_id', 'date', 'period']);

      $stats = $this->printManagementService->getPrintStats($filters);

      return PrintApiResponse::success('Lấy thống kê thành công', $stats);
    } catch (\Exception $e) {
      Log::error('Get print stats failed', [
        'error' => $e->getMessage()
      ]);

      return PrintApiResponse::error('Lỗi lấy thống kê: ' . $e->getMessage());
    }
  }
}
