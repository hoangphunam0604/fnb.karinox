<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TableAndRoomRequest;
use App\Http\Resources\Admin\TableAndRoomResource;
use App\Services\TableAndRoomService;
use Illuminate\Http\Request;

class TableAndRoomController extends Controller
{
  public function __construct(
    protected TableAndRoomService $tableAndRoomService
  ) {}

  public function index(Request $request)
  {
    $items = $this->tableAndRoomService->getList($request->all());
    return TableAndRoomResource::collection($items);
  }

  public function store(TableAndRoomRequest $request)
  {
    $item = $this->tableAndRoomService->create($request->validated());
    return new TableAndRoomResource($item);
  }

  public function show($id)
  {
    $item = $this->tableAndRoomService->find($id);
    return new TableAndRoomResource($item);
  }

  public function update(TableAndRoomRequest $request, $id)
  {
    $item = $this->tableAndRoomService->update($id, $request->validated());
    return new TableAndRoomResource($item);
  }

  public function destroy($id)
  {
    $this->tableAndRoomService->delete($id);
    return response()->json(['message' => 'Deleted successfully']);
  }
}
