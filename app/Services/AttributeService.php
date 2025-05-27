<?php

namespace App\Services;

use App\Models\Attribute;
use Illuminate\Database\Eloquent\Model;

class AttributeService extends BaseService
{
  protected function model(): Model
  {
    return new Attribute();
  }
}
