<?php

namespace App\Http\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Admin\Requests\AreaRequest;
use App\Http\Admin\Resources\AreaResource;
use App\Services\AreaService;
use Illuminate\Http\Request;

class AreaController extends Controller
{
  public function __construct(protected AreaService $service) {}

  public function index(Request $request)
  {
    $areas = $this->service->getList($request->all());
    $areas->load('tablesAndRooms');
    return AreaResource::collection($areas);
  }

  public function store(AreaRequest $request)
  {
    $data = $request->validated();
    $data['branch_id'] = $request->input('branch_id') ?? (app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null);

    if (!$data['branch_id']) {
      return response()->json(['error' => 'Vui lòng chọn chi nhánh'], 400);
    }

    $area = $this->service->create($data);
    $area->load('tablesAndRooms');
    return new AreaResource($area);
  }

  public function show($id)
  {
    $area = $this->service->find($id);
    $area->load('tablesAndRooms');
    return new AreaResource($area);
  }

  public function update(AreaRequest $request, $id)
  {
    $area = $this->service->update($id, $request->validated());
    return new AreaResource($area);
  }

  public function destroy($id)
  {
    $this->service->delete($id);
    return response()->json(['message' => 'Deleted successfully.']);
  }
}
