<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use App\Services\BranchService;
use App\Services\ProductImportService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ProductImportController extends Controller
{
  protected $branchService;
  protected $productImportService;

  public function __construct(BranchService $branchService, ProductImportService $productImportService)
  {
    $this->branchService = $branchService;
    $this->productImportService = $productImportService;
  }
  public function index()
  {
    $user = Auth::user();
    $branches = $this->branchService->getUserBranches($user);
    return Inertia::render(
      'Products/Import',
      [
        'branches' => $branches
      ]
    );
  }

  public function import(Request $request)
  {
    $request->validate(['branch_id' => 'required', 'file' => 'required|mimes:xlsx']);

    if (!$request->hasFile('file')) {
      return response()->json(['error' => 'Không có file nào được tải lên.'], 400);
    }

    $file = $request->file('file');

    if (!$file->isValid()) {
      return response()->json(['error' => 'File tải lên bị lỗi.'], 400);
    }
    // Kiểm tra tên file và kích thước

    // Lưu file vào storage/temp
    //$filePath = $file->store('temp');
    $filePath = $file->move(public_path('uploads'), time() . '-' . $file->getClientOriginalName());

    if (!$filePath) {
      return response()->json(['error' => 'Lưu file thất bại.'], 500);
    }


    $fullPath = public_path('uploads/' . basename($filePath));

    $branch_id = $request->branch_id;
    $result = $this->productImportService->importFromExcel($branch_id, $fullPath);

    return response()->json($result);
  }
}
