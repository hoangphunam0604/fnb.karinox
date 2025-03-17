<?php

namespace App\Services;

use App\Models\TableAndRoom;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class TableAndRoomService
{

  public function getAreaTablesByBra(int $branchId)
  {
    return TableAndRoom::where('branch_id', $branchId)->all();
  }
  /**
   * Tạo hoặc cập nhật phòng/bàn
   */
  public function saveTableOrRoom(array $data, $id = null)
  {
    return DB::transaction(function () use ($data, $id) {
      $tableOrRoom = $id
        ? TableAndRoom::findOrFail($id)
        : new TableAndRoom();

      $tableOrRoom->fill([
        'name' => $data['name'] ?? $tableOrRoom->name,
        'area_id' => $data['area_id'] ?? $tableOrRoom->area_id,
        'capacity' => $data['capacity'] ?? $tableOrRoom->capacity,
        'status' => $data['status'] ?? 'available', // Trạng thái mặc định là 'available'
        'note' => $data['note'] ?? $tableOrRoom->note,
      ]);

      $tableOrRoom->save();
      return $tableOrRoom;
    });
  }

  /**
   * Xóa phòng/bàn
   */
  public function deleteTableOrRoom($id)
  {
    return TableAndRoom::findOrFail($id)->delete();
  }

  /**
   * Tìm kiếm phòng/bàn theo tên
   */
  public function findTableOrRoom($keyword)
  {
    return TableAndRoom::where('name', 'LIKE', "%{$keyword}%")->first();
  }

  /**
   * Lấy danh sách tất cả phòng/bàn (phân trang)
   */
  public function getTablesAndRooms($perPage = 10)
  {
    return TableAndRoom::orderBy('created_at', 'desc')->paginate($perPage);
  }

  /**
   * Lấy danh sách phòng/bàn theo trạng thái
   */
  public function getTablesAndRoomsByStatus($status, $perPage = 10)
  {
    return TableAndRoom::byStatus($status)->paginate($perPage);
  }

  /**
   * Cập nhật trạng thái phòng/bàn
   */
  public function updateTableOrRoomStatus($id, $status)
  {
    $tableOrRoom = TableAndRoom::findOrFail($id);
    $tableOrRoom->status = $status;
    $tableOrRoom->save();

    return $tableOrRoom;
  }

  /**
   * Kiểm tra xem phòng/bàn có thể sử dụng không
   */
  public function canUseTableOrRoom($id)
  {
    $tableOrRoom = TableAndRoom::findOrFail($id);
    return $tableOrRoom->isAvailable();
  }
}
