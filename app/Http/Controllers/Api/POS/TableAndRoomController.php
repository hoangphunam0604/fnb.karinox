<?php

namespace App\Http\Controllers\Api\POS;

use App\Http\Controllers\Controller;
use App\Http\Resources\POS\AreaResource;
use App\Services\AreaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class TableAndRoomController extends Controller
{
  protected $areaService;

  public function __construct(AreaService $areaService)
  {
    $this->areaService = $areaService;
  }

  public function list(): JsonResponse
  {
    /** @var User|null $user */
    $user = Auth::user();

    if (!$user || !$user->current_branch) {
      return response()->json([
        'success' => false,
        'message' => 'Người dùng hoặc chi nhánh không hợp lệ',
      ], 400);
    }

    $areas = $this->areaService->getAreasByBranch($user->current_branch);

    return response()->json([
      'success' => true,
      'data' => AreaResource::collection($areas),
    ]);
  }
}
