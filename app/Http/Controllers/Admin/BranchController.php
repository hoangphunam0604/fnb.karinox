<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BranchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
  protected $branchService;

  public function __construct(BranchService $branchService)
  {
    $this->branchService = $branchService;
  }

  /**
   * Hiển thị danh sách chi nhánh (Index).
   */
  public function index(): Response
  {
    $branches = $this->branchService->getActiveBranches();
    return Inertia::render('Branches/Index', [
      'title' => 'Quản lý Chi nhánh',
      'branches' => $branches,
    ]);
  }

  /**
   * Hiển thị form tạo mới chi nhánh (Create).
   */
  public function create(): Response
  {
    return Inertia::render('Branches/Create', ['title' => "ABC"]);
  }

  /**
   * Lưu chi nhánh mới vào database (Store).
   */
  public function store(Request $request)
  {
    $validatedData = $request->validate([
      'name' => 'required|string|max:255',
      'address' => 'required|string|max:255',
      'phone_number' => 'nullable|string|max:20',
      'email' => 'nullable|email|max:255',
    ]);

    $this->branchService->createBranch($validatedData);

    return redirect()->route('admin.branches.index')->with('success', 'Chi nhánh được tạo thành công!');
  }

  /**
   * Hiển thị chi tiết chi nhánh (Show).
   */
  public function show($id): Response
  {
    $branch = $this->branchService->findById($id);

    return Inertia::render('Branches/Show', [
      'branch' => $branch,
    ]);
  }

  /**
   * Hiển thị form chỉnh sửa chi nhánh (Edit).
   */
  public function edit($id): Response
  {
    $branch = $this->branchService->findById($id);

    return Inertia::render('Branches/Edit', [
      'branch' => $branch,
    ]);
  }

  /**
   * Cập nhật chi nhánh trong database (Update).
   */
  public function update(Request $request, $id)
  {
    $validatedData = $request->validate([
      'name' => 'required|string|max:255',
      'address' => 'required|string|max:255',
      'phone_number' => 'nullable|string|max:20',
      'email' => 'nullable|email|max:255',
    ]);

    $this->branchService->updateBranch($id, $validatedData);

    return redirect()->route('admin.branches.index')->with('success', 'Chi nhánh được cập nhật thành công!');
  }

  /**
   * Xóa chi nhánh khỏi database (Destroy).
   */
  public function destroy($id)
  {
    $this->branchService->deleteBranch($id);

    return redirect()->route('admin.branches.index')->with('success', 'Chi nhánh được xóa thành công!');
  }
}
