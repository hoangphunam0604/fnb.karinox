<?php

namespace App\Services;

use App\Models\Area;
use Illuminate\Support\Facades\DB;

class AreaService
{

  public function getAreasByBranch($branchId)
  {
    return Area::with(['tablesAndRooms'])->where('branch_id', $branchId)->get();
  }
  /**
   * Tạo hoặc cập nhật khu vực
   */
  public function saveArea(array $data, $areaId = null)
  {
    return DB::transaction(function () use ($data, $areaId) {
      $area = $areaId
        ? Area::findOrFail($areaId)
        : new Area();

      $area->fill([
        'name' => $data['name'] ?? $area->name,
        'branch_id' => $data['branch_id'] ?? $area->branch_id,
        'note' => $data['note'] ?? $area->note,
      ]);

      $area->save();
      return $area;
    });
  }

  /**
   * Xóa khu vực
   */
  public function deleteArea($areaId)
  {
    return Area::findOrFail($areaId)->delete();
  }

  /**
   * Tìm kiếm khu vực theo tên
   */
  public function findArea($keyword)
  {
    return Area::where('name', 'LIKE', "%{$keyword}%")->first();
  }

  /**
   * Lấy danh sách tất cả khu vực (phân trang)
   */
  public function getAreas($perPage = 10)
  {
    return Area::orderBy('created_at', 'desc')->paginate($perPage);
  }
}
