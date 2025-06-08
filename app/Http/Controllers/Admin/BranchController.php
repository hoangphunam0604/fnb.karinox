<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchRequest;
use App\Http\Resources\Admin\BranchResource;
use App\Services\BranchService;
use Illuminate\Http\Request;

class BranchController extends Controller
{
  protected BranchService $branchService;

  public function __construct(BranchService $branchService)
  {
    $this->branchService = $branchService;
  }
  public function all(Request $request)
  {
    $status = $request->status;
    $branches = $this->branchService->getAll($status);
    return BranchResource::collection($branches);
  }
  public function index(Request $request)
  {
    $branches = $this->branchService->getList($request->all());
    return BranchResource::collection($branches);
  }

  public function store(BranchRequest $request)
  {
    $branch = $this->branchService->create($request->validated());
    return new BranchResource($branch);
  }

  public function show($id)
  {
    $branch = $this->branchService->find($id);
    return new BranchResource($branch);
  }

  public function update(BranchRequest $request, $id)
  {
    $branch = $this->branchService->update($id, $request->validated());
    return new BranchResource($branch);
  }

  public function destroy($id)
  {
    $this->branchService->delete($id);
    return response()->json(['message' => 'Deleted successfully.']);
  }
}
