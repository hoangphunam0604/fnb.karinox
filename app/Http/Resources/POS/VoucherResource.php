<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'code'  =>  $this->code,
      'description' =>  $this->description,
      'uses_today'  =>  $this->uses_today,
      'remaining_uses_today' =>  $this->remaining_uses_today,
      'per_customer_daily_limit' =>  $this->per_customer_daily_limit,
      'uses_total'  =>  $this->uses_total,
      'remaining_uses_total' =>  $this->remaining_uses_total,
      'per_customer_limit'  =>  $this->per_customer_limit,
      'remaining_uses'  =>  $this->remaining_uses,
      'is_usable' =>  $this->is_usable,
    ];
  }
}
