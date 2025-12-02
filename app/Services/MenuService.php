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
}
