<?php

namespace App\Http\Common\Controllers;

use App\Enums\CommonStatus;
use App\Http\Common\Controllers\Controller;
use App\Http\Common\Resources\BranchResource;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\BranchService;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
  protected $branchService;

  public function __construct(BranchService $branchService)
  {
    $this->branchService = $branchService;
  }

  public function getUserBranches()
  {
    $branches = $this->branchService->getAll();
    return BranchResource::collection($branches);
  }


  public function selectBranch(Request $request)
  {

    /** @var User|null $user */
    $user = Auth::user();
    $branches = $this->branchService->getAll(CommonStatus::ACTIVE);
    // Lấy danh sách chi nhánh mà user quản lý
    $managedBranches = $branches->pluck('id')->toArray();

    // Kiểm tra xem chi nhánh có tồn tại và thuộc quyền quản lý không
    if (!in_array($request->branch_id, $managedBranches)) {
      return response()->json([
        'success' => false,
        'message' => 'Bạn không có quyền quản lý chi nhánh này!'
      ], 403);
    }

    // Nếu hợp lệ, cập nhật current_branch
    $user->current_branch = $request->branch_id;
    $user->save();

    return response()->json([
      'success' => true,
      'message' => 'Chi nhánh đã được chọn thành công!',
      'current_branch' => $user->currentBranch
    ], 200);
  }
}
