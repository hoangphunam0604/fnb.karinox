<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BranchService;

class BranchController extends Controller
{
  protected $branchService;

  public function __construct(BranchService $branchService)
  {
    $this->branchService = $branchService;
  }

  public function store(Request $request)
  {
    $branch = $this->branchService->createBranch($request->all());
    return response()->json($branch);
  }

  public function update(Request $request, $id)
  {
    $branch = $this->branchService->updateBranch($id, $request->all());
    return response()->json($branch);
  }

  public function destroy($id)
  {
    $this->branchService->deleteBranch($id);
    return response()->json(['message' => 'Chi nhánh đã được xóa']);
  }

  public function search(Request $request)
  {
    $branch = $this->branchService->findBranch($request->input('keyword'));
    return response()->json($branch);
  }

  public function index()
  {
    $branches = $this->branchService->getBranches();
    return response()->json($branches);
  }
}
