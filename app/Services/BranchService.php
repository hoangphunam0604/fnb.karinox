<?php

namespace App\Services;

use App\Enums\CommonStatus;
use Illuminate\Database\Eloquent\Model;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class BranchService extends BaseService
{
  protected function model(): Model
  {
    return new Branch();
  }
  public function getAll(?CommonStatus $status = null)
  {
    $query = $this->model()->newQuery();
    if ($status) {
      $query->whereStatus($status);
    }
    return $query->orderBy('sort_order', 'desc')->get();
  }
}
