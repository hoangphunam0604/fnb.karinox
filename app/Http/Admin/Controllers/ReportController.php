<?php

namespace App\Http\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
  protected ReportService $reportService;

  public function __construct(ReportService $reportService)
  {
    $this->reportService = $reportService;
  }

  /**
   * Báo cáo doanh thu theo nhân viên theo ngày
   */
  public function dailySalesByEmployee(Request $request): JsonResponse
  {
    $request->validate([
      'date' => 'required|date',
      'branch_id' => 'nullable|integer|exists:branches,id'
    ]);

    $report = $this->reportService->getDailySalesReportByEmployee(
      $request->date,
      $request->branch_id
    );

    return response()->json([
      'success' => true,
      'data' => $report
    ]);
  }

  /**
   * Báo cáo doanh thu theo nhân viên theo khoảng thời gian
   */
  public function salesByEmployeePeriod(Request $request): JsonResponse
  {
    $request->validate([
      'start_date' => 'required|date',
      'end_date' => 'required|date|after_or_equal:start_date',
      'branch_id' => 'nullable|integer|exists:branches,id'
    ]);

    $report = $this->reportService->getSalesReportByEmployeePeriod(
      $request->start_date,
      $request->end_date,
      $request->branch_id
    );

    return response()->json([
      'success' => true,
      'data' => $report
    ]);
  }
}
