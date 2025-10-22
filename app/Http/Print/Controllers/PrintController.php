<?php

namespace App\Http\Print\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Print\Requests\ConnectRequest;
use App\Http\Print\Requests\ConfirmPrintRequest;
use App\Http\Print\Requests\ReportErrorRequest;
use App\Http\Print\Requests\PrintHistoryRequest;
use App\Http\Print\Requests\PrintStatsRequest;
use App\Http\Print\Responses\PrintApiResponse;
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
    try {
      $data = $this->printManagementService->connectToBranch(
        $request->validated('connection_code')
      );

      return PrintApiResponse::success('Kết nối thành công', $data);
    } catch (\Exception $e) {
      Log::error('Print connection failed', [
        'connection_code' => $request->validated('connection_code'),
        'error' => $e->getMessage()
      ]);

      return PrintApiResponse::error('Lỗi kết nối: ' . $e->getMessage(), 404);
    }
  }

  /**
   * Xác nhận đã in thành công
   * POST /api/print/confirm
   */
  public function confirm(ConfirmPrintRequest $request): JsonResponse
  {
    try {
      $data = $this->printManagementService->confirmPrint(
        $request->validated('print_id'),
        $request->validated('device_id'),
        $request->validated('status', 'printed')
      );

      return PrintApiResponse::success('Đã xác nhận print job', $data);
    } catch (\Exception $e) {
      Log::error('Confirm print failed', [
        'print_id' => $request->validated('print_id'),
        'error' => $e->getMessage()
      ]);

      return PrintApiResponse::error('Lỗi xác nhận print: ' . $e->getMessage(), 404);
    }
  }

  /**
   * Báo lỗi print job
   * POST /api/print/error
   */
  public function reportError(ReportErrorRequest $request): JsonResponse
  {
    try {
      $this->printManagementService->reportPrintError(
        $request->validated('print_id'),
        $request->validated('device_id'),
        $request->validated('error_type'),
        $request->validated('error_message'),
        $request->validated('error_details')
      );

      return PrintApiResponse::success('Đã báo lỗi print job');
    } catch (\Exception $e) {
      Log::error('Report print error failed', [
        'print_id' => $request->validated('print_id'),
        'error' => $e->getMessage()
      ]);

      return PrintApiResponse::error('Lỗi báo cáo lỗi: ' . $e->getMessage(), 404);
    }
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
