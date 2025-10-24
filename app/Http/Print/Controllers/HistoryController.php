<?php

namespace App\Http\Print\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Print\Resources\PrintHistoryResource;
use App\Models\PrintHistory;
use App\Services\PrintHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HistoryController extends Controller
{
  public function __construct(private PrintHistoryService $service) {}

  public function index(Request $request)
  {
    $items = $this->service->getList($request->all());
    $items->load('branch');
    return PrintHistoryResource::collection($items);
  }

  /**
   * Xác nhận đã in thành công
   * POST /api/print/histories/{printHistory}/confirm
   */
  public function confirm(PrintHistory $printHistory): JsonResponse
  {
    $this->service->confirmPrint($printHistory);
    return response()->json(
      [
        'success' => true,
        'message' => 'Xác nhận in thành công',
      ]
    );
  }

  /**
   * Báo lỗi print job
   * POST /api/print/histories/{printHistory}/error
   */
  public function reportError(PrintHistory $printHistory): JsonResponse
  {
    $this->service->markPrintFailed($printHistory);

    return response()->json(
      [
        'success' => true,
        'message' => 'Đã báo lỗi print job'
      ]
    );
  }
}
