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
  public function delete($id): bool
  {
    if ($id == 1)
      throw new \Exception("Không thể xóa thực đơn chính");
    return parent::delete($id);
  }
}
