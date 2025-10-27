<?php

namespace App\Http\Admin\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\Admin\Requests\TableAndRoomRequest;
use App\Http\Admin\Resources\TableAndRoomResource;
use App\Services\TableAndRoomService;
use Illuminate\Http\Request;

class TableAndRoomController extends Controller
{
  public function __construct(protected TableAndRoomService $service) {}

  public function index(Request $request)
  {
    $items = $this->service->getList($request->all());
    return TableAndRoomResource::collection($items);
  }

  public function store(TableAndRoomRequest $request)
  {
    $item = $this->service->create($request->validated());
    return new TableAndRoomResource($item);
  }

  public function show($id)
  {
    $item = $this->service->find($id);
    return new TableAndRoomResource($item);
  }

  public function update(TableAndRoomRequest $request, $id)
  {
    $item = $this->service->update($id, $request->validated());
    return new TableAndRoomResource($item);
  }

  public function destroy($id)
  {
    $this->service->delete($id);
    return response()->json(['message' => 'Deleted successfully']);
  }
}
