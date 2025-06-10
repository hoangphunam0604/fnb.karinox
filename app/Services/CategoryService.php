<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;

class CategoryService extends BaseService
{
  protected function model(): Model
  {
    return new Category();
  }
}
