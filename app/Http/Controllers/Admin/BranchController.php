<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommonStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchRequest;
use App\Http\Resources\Admin\BranchResource;
use App\Services\BranchService;
use Illuminate\Http\Request;

class BranchController extends Controller
{
  public function __construct(protected BranchService $service) {}
  public function all(Request $request)
  {
    $status = CommonStatus::tryFrom($request->status) ?? null;
    $branches = $this->service->getAll($status);
    return BranchResource::collection($branches);
  }
  public function index(Request $request)
  {
    $branches = $this->service->getList($request->all());
    return BranchResource::collection($branches);
  }

  public function store(BranchRequest $request)
  {
    $branch = $this->service->create($request->validated());
    return new BranchResource($branch);
  }

  public function show($id)
  {
    $branch = $this->service->find($id);
    return new BranchResource($branch);
  }

  public function update(BranchRequest $request, $id)
  {
    $branch = $this->service->update($id, $request->validated());
    return new BranchResource($branch);
  }

  public function destroy($id)
  {
    $this->service->delete($id);
    return response()->json(['message' => 'Deleted successfully.']);
  }
}
