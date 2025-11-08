<?php

namespace App\Services;

use App\Models\TableAndRoom;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class TableAndRoomService  extends BaseService
{

  protected array $with = ['area'];
  protected function model(): Model
  {
    return new TableAndRoom();
  }
  protected function applySearch($query, array $params)
  {
    // $query = parent::applySearch($query, $params);
    if (!empty($params['keyword'])) {
      $query->where('name', 'like', '%' . $params['keyword'] . '%');
    }

    if (!empty($params['area_id'])) {
      $query->where('area_id', $params['area_id']);
    }
    if (!empty($params['status'])) {
      $query->where('status', $params['status']);
    }
    return $query;
  }
}
