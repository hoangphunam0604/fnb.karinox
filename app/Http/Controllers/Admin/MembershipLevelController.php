<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MembershipLevelRequest;
use App\Http\Resources\Admin\MembershipLevelResource;
use App\Http\Resources\Admin\MembershipLevelDetailResource;
use App\Services\MembershipLevelService;
use Illuminate\Http\Request;

class MembershipLevelController extends Controller
{
  protected MembershipLevelService $service;

  public function __construct(MembershipLevelService $service)
  {
    $this->service = $service;
  }

  public function index(Request $request)
  {
    $items = $this->service->getList($request->all());
    return MembershipLevelResource::collection($items);
  }

  public function store(MembershipLevelRequest $request)
  {
    $item = $this->service->create($request->validated());
    return new MembershipLevelResource($item);
  }

  public function show($id)
  {
    $item = $this->service->find($id);
    return new MembershipLevelDetailResource($item);
  }

  public function update(MembershipLevelRequest $request, $id)
  {
    $item = $this->service->update($id, $request->validated());
    return $item;
    return new MembershipLevelResource($item);
  }

  public function destroy($id)
  {
    $this->service->delete($id);
    return response()->json(['message' => 'Deleted successfully.']);
  }
}
