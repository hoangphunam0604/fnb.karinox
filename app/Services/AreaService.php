<?php

namespace App\Services;

use App\Models\Area;
use Illuminate\Database\Eloquent\Model;

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
}
