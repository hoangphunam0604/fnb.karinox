<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class BranchService
{
  /**
   * Tạo chi nhánh mới
   */
  public function createBranch(array $data)
  {
    return Branch::create($data);
  }

  /**
   * Cập nhật thông tin chi nhánh
   */
  public function updateBranch($branchId, array $data)
  {
    $branch = Branch::findOrFail($branchId);
    $branch->update($data);
    return $branch;
  }

  /**
   * Xóa chi nhánh (KHÔNG kiểm tra sản phẩm và hóa đơn)
   */
  public function deleteBranch($branchId)
  {
    $branch = Branch::findOrFail($branchId);
    return $branch->delete();
  }
  /**
   * Lấy chi nhánh theo id
   */
  public function findById($id): Branch
  {
    return Branch::findOrFail($id);
  }
  /**
   * Tìm kiếm chi nhánh theo tên, địa chỉ hoặc số điện thoại
   */
  public function findBranch($keyword)
  {
    return Branch::where('name', 'LIKE', "%{$keyword}%")
      ->orWhere('address', 'LIKE', "%{$keyword}%")
      ->orWhere('phone_number', 'LIKE', "%{$keyword}%")
      ->first();
  }

  /**
   * Lấy danh sách tất cả chi nhánh (phân trang)
   */
  public function getBranches($perPage = 10)
  {
    return Branch::orderBy('sort_order', 'asc')->paginate($perPage);
  }

  public function getActiveBranches()
  {
    return Branch::whereStatus('active')->orderBy('sort_order', 'asc')->get();
  }
}
