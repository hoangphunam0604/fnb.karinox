<?php

namespace App\Http\Controllers\Api\POS;

use App\Http\Controllers\Controller;
use App\Http\Resources\POS\AreaResource;
use App\Services\AreaService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TableController extends Controller
{
  protected $areaService;

  public function __construct(AreaService $areaService)
  {
    $this->areaService = $areaService;
  }

  public function list()
  {
    /** @var User|null $user */
    $user = Auth::user();
    $areas = $this->areaService->getAreasByBranch($user->current_branch);
    return Inertia::render('TablesAndRooms', [
      'areas' => AreaResource::collection($areas)->resolve()
    ]);
    return response()->json($areas);
  }
}
