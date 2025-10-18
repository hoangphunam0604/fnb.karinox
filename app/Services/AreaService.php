<?php

namespace App\Services;

use App\Models\Area;
use App\Models\TableAndRoom;
use App\Enums\TableAndRoomStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AreaService extends BaseService
{
  protected function model(): Model
  {
    return new Area();
  }

  public function getByBranch($branchId)
  {
    return Area::with(['tablesAndRooms'])->where('branch_id', $branchId)->get();
  }

  /**
   * Tạo khu vực mới với phòng/bàn tự động
   */
  public function create(array $data): Model
  {
    return DB::transaction(function () use ($data) {
      // Tạo khu vực
      $area = parent::create([
        'name' => $data['name'],
        'branch_id' => $data['branch_id'] ?? (app()->bound('karinox_branch_id') ? app('karinox_branch_id') : null),
        'note' => $data['note'] ?? null,
      ]);

      // Tạo phòng/bàn nếu có thông tin
      if (isset($data['table_count']) && $data['table_count'] > 0) {
        $this->createTablesForArea($area, $data);
      }

      return $area->load('tablesAndRooms');
    });
  }

  /**
   * Cập nhật khu vực
   */
  public function update($id, array $data)
  {
    return DB::transaction(function () use ($id, $data) {
      $area = parent::update($id, [
        'name' => $data['name'],
        'note' => $data['note'] ?? null,
      ]);

      // Tạo thêm phòng/bàn nếu có yêu cầu
      if (isset($data['table_count']) && $data['table_count'] > 0) {
        $this->createTablesForArea($area, $data);
      }

      return $area->load('tablesAndRooms');
    });
  }

  /**
   * Tạo phòng/bàn cho khu vực
   */
  private function createTablesForArea(Area $area, array $data)
  {
    $tablePrefix = $data['table_prefix'] ?? 'Bàn';
    $tableCount = $data['table_count'];
    $tableCapacity = $data['table_capacity'] ?? 4; // Mặc định 4 chỗ ngồi nếu không chỉ định

    // Lấy số bàn hiện tại để tránh trùng lặp
    $existingCount = $area->tablesAndRooms()->count();

    $tables = [];
    for ($i = 1; $i <= $tableCount; $i++) {
      $tableNumber = $existingCount + $i;
      $tables[] = [
        'name' => $tablePrefix . ' ' . str_pad($tableNumber, 2, '0', STR_PAD_LEFT),
        'area_id' => $area->id,
        'capacity' => $tableCapacity,
        'status' => TableAndRoomStatus::AVAILABLE,
        'created_at' => now(),
        'updated_at' => now(),
      ];
    }

    if (!empty($tables)) {
      TableAndRoom::insert($tables);
    }
  }
}
