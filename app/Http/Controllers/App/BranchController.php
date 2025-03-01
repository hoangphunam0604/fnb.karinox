<?php

namespace App\Http\Controllers\App;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\App\BranchResource;
use App\Models\Branch;
use Illuminate\Http\Request;
use App\Services\BranchService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BranchController extends Controller
{
  protected $branchService;

  public function __construct(BranchService $branchService)
  {
    $this->branchService = $branchService;
  }

  public function getUserBranches(Request $request)
  {
    /** @var User|null $user */
    $user = Auth::user();
    // Nếu là admin, trả về tất cả chi nhánh
    if ($user->hasRole(UserRole::ADMIN)) {
      $branches = Branch::all();
    } else {
      // Nếu không phải admin, chỉ trả về các chi nhánh được phân công
      $branches = $user->branches->get();
    } // Kiểm tra dữ liệu trước khi dùng Resource
    return Inertia::render('Branches', [
      'branches' => BranchResource::collection($branches)->resolve()
    ]);


    return response()->json($branches);
  }

  public function selectBranch(Request $request)
  {
    $branch = Branch::findOrFail($request->branch_id);
    /** @var User|null $user */
    $user = Auth::user();
    $user->current_branch = $branch->id;
    $user->save();
    $auth_redirect = $user->login_redirect;
    return redirect()->to($auth_redirect);
  }
}
