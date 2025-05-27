<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AreaRequest;
use App\Http\Resources\Admin\AreaResource;
use App\Services\AreaService;
use Illuminate\Http\Request;

class AreaController extends Controller
{
  protected AreaService $areaService;

  public function __construct(AreaService $areaService)
  {
    $this->areaService = $areaService;
  }

  public function index(Request $request)
  {
    $areas = $this->areaService->getList($request->all());
    return AreaResource::collection($areas);
  }

  public function store(AreaRequest $request)
  {
    $area = $this->areaService->create($request->validated());
    return new AreaResource($area);
  }

  public function show($id)
  {
    $area = $this->areaService->find($id);
    return new AreaResource($area);
  }

  public function update(AreaRequest $request, $id)
  {
    $area = $this->areaService->update($id, $request->validated());
    return new AreaResource($area);
  }

  public function destroy($id)
  {
    $this->areaService->delete($id);
    return response()->json(['message' => 'Deleted successfully.']);
  }
}
