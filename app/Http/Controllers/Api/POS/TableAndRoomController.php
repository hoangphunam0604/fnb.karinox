<?php

namespace App\Http\Controllers\Api\POS;

use App\Http\Controllers\Controller;
use App\Http\Resources\POS\AreaResource;
use App\Services\AreaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TableAndRoomController extends Controller
{
  protected $areaService;

  public function __construct(AreaService $areaService)
  {
    $this->areaService = $areaService;
  }

  public function list(): JsonResponse
  {
    $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null;
    $areas = $this->areaService->getAreasByBranch($branchId);

    return response()->json([
      'success' => true,
      'karinox_branch_id' => $branchId,
      'data' => AreaResource::collection($areas),
    ]);
  }
}
