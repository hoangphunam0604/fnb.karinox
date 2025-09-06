<?php

namespace App\Services;

use App\Models\MembershipLevel;

class MembershipLevelService extends BaseService
{

  protected function model(): MembershipLevel
  {
    return new MembershipLevel();
  }

  protected function applySearch($query, array $params)
  {
    if (!empty($params['keyword'])):
      $keyword = $params['keyword'];
      $query->where('name', 'LIKE', '%' . $keyword . '%');
    endif;
    return $query;
  }
}
