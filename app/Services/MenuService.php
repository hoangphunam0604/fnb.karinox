<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Model;

class MenuService extends BaseService
{


  protected function model(): Model
  {
    return new Menu();
  }
  public function create(array $data): Model
  {
    $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : $data['branch_id'];
    return parent::create(array_merge($data, [
      'branch_id' => $branchId
    ]));
  }
}
