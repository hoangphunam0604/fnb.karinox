<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\PrintHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PrintManagementService
{
  /**
   * Kết nối với chi nhánh qua connection code
   */
  public function connectToBranch(string $connectionCode): array
  {
    $branch = Branch::findByConnectionCode($connectionCode);

    if (!$branch) {
      throw new \Exception('Mã kết nối không hợp lệ hoặc chi nhánh không hoạt động');
    }

    return [
      'branch_id' => $branch->id,
      'branch_name' => $branch->name,
      'branch_address' => $branch->address,
      'branch_phone' => $branch->phone_number,
      'websocket_url' => $this->getWebSocketUrl(),
      'channel_name' => "print-branch-{$branch->id}",
      'event_name' => 'print.requested'
    ];
  }

  /**
   * Xác nhận print job thành công
   */
  public function confirmPrint(string $printId, string $deviceId, string $status = 'printed'): array
  {
    $printHistory = PrintHistory::where('print_id', $printId)->first();

    if (!$printHistory) {
      throw new \Exception('Print job không tồn tại');
    }

    $printHistory->update([
      'status' => $status,
      'printed_at' => now(),
      'confirmed_at' => $status === 'confirmed' ? now() : null,
      'print_duration' => now()->diffInSeconds($printHistory->requested_at)
    ]);

    return [
      'print_id' => $printId,
      'status' => $printHistory->status,
      'duration' => $printHistory->print_duration . 's'
    ];
  }

  /**
   * Báo lỗi print job
   */
  public function reportPrintError(string $printId, string $deviceId, string $errorType, string $errorMessage, ?array $errorDetails = null): void
  {
    $printHistory = PrintHistory::where('print_id', $printId)->first();

    if (!$printHistory) {
      throw new \Exception('Print job không tồn tại');
    }

    $printHistory->update([
      'status' => 'failed',
      'error_message' => $errorMessage,
      'error_details' => $errorDetails ? json_encode($errorDetails) : null,
      'print_duration' => now()->diffInSeconds($printHistory->requested_at)
    ]);
  }

  /**
   * Lấy lịch sử in
   */
  public function getPrintHistory(array $filters = [], int $perPage = 20)
  {
    $query = PrintHistory::query();

    if (!empty($filters['device_id'])) {
      $query->where('device_id', $filters['device_id']);
    }

    if (!empty($filters['branch_id'])) {
      $query->where('branch_id', $filters['branch_id']);
    }

    if (!empty($filters['status'])) {
      $query->where('status', $filters['status']);
    }

    if (!empty($filters['from_date'])) {
      $query->whereDate('requested_at', '>=', $filters['from_date']);
    }

    if (!empty($filters['to_date'])) {
      $query->whereDate('requested_at', '<=', $filters['to_date']);
    }

    return $query->orderBy('requested_at', 'desc')->paginate($perPage);
  }

  /**
   * Lấy thống kê in
   */
  public function getPrintStats(array $filters = []): array
  {
    $query = PrintHistory::query();

    // Xử lý period
    if (!empty($filters['period'])) {
      $query = $this->applyPeriodFilter($query, $filters['period']);
    } elseif (!empty($filters['date'])) {
      $query->whereDate('requested_at', $filters['date']);
    } else {
      $query->whereDate('requested_at', today());
    }

    if (!empty($filters['branch_id'])) {
      $query->where('branch_id', $filters['branch_id']);
    }

    if (!empty($filters['device_id'])) {
      $query->where('device_id', $filters['device_id']);
    }

    $baseQuery = clone $query;

    return [
      'total_jobs' => $baseQuery->count(),
      'completed_jobs' => (clone $query)->where('status', 'confirmed')->count(),
      'failed_jobs' => (clone $query)->where('status', 'failed')->count(),
      'pending_jobs' => (clone $query)->where('status', 'requested')->count(),
      'avg_duration' => (clone $query)->whereNotNull('print_duration')->avg('print_duration'),
      'period' => $filters['period'] ?? 'custom',
      'date' => $filters['date'] ?? today()->toDateString()
    ];
  }

  /**
   * Áp dụng filter theo period
   */
  private function applyPeriodFilter($query, string $period)
  {
    switch ($period) {
      case 'today':
        return $query->whereDate('requested_at', today());
      case 'yesterday':
        return $query->whereDate('requested_at', today()->subDay());
      case 'week':
        return $query->whereBetween('requested_at', [
          now()->startOfWeek(),
          now()->endOfWeek()
        ]);
      case 'month':
        return $query->whereBetween('requested_at', [
          now()->startOfMonth(),
          now()->endOfMonth()
        ]);
      default:
        return $query->whereDate('requested_at', today());
    }
  }

  /**
   * Lấy WebSocket URL
   */
  private function getWebSocketUrl(): ?string
  {
    $appKey = config('broadcasting.connections.reverb.app_key');
    $port = config('broadcasting.connections.reverb.port', 6001);

    return $appKey ? "ws://localhost:{$port}/app/{$appKey}" : null;
  }
}
