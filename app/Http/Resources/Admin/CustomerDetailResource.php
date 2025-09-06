<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

class CustomerDetailResource extends CustomerResource
{
  /**
   * @param Request $request
   */
  public function toArray($request): array
  {
    return array_merge(parent::toArray($request), []);
  }
}
