<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AreaRequest;
use App\Http\Resources\Admin\AreaResource;
use App\Services\AreaService;
use Illuminate\Http\Request;

class AreaController extends Controller
{
  public function __construct(protected AreaService $service) {}

  public function index(Request $request)
  {
    $areas = $this->service->getList($request->all());
    return AreaResource::collection($areas);
  }

  public function store(AreaRequest $request)
  {
    $area = $this->service->create($request->validated());
    return new AreaResource($area);
  }

  public function show($id)
  {
    $area = $this->service->find($id);
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
