<?php

namespace App\Http\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Admin\Requests\MembershipLevelRequest;
use App\Http\Admin\Resources\MembershipLevelResource;
use App\Http\Admin\Resources\MembershipLevelDetailResource;
use App\Services\MembershipLevelService;
use Illuminate\Http\Request;

class MembershipLevelController extends Controller
{

  public function __construct(protected MembershipLevelService $service) {}

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
