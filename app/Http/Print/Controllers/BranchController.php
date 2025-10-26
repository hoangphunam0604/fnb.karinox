<?php

namespace App\Http\Print\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Print\Resources\BranchResource;
use App\Services\BranchService;
use App\Services\WebSocketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BranchController extends Controller
{
  public function __construct(private BranchService $branchService, private WebSocketService $webSocketService) {}

  public function connect(string $connection_code): JsonResponse
  {
    $branch = $this->branchService->findByConnectionCode($connection_code);
    $websocket_config = $this->webSocketService->getWebSocketConfig("print-branch-{$branch->id}", 'print.requested');
    return response()->json(
      [
        'success' => true,
        'message' => 'Kết nối thành công',
        'data' => [
          'branch'  => new BranchResource($branch),
          'websocket_config' => $websocket_config
        ]
      ]
    );
  }
}
