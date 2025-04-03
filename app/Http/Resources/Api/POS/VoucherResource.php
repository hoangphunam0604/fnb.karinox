<?php

namespace App\Http\Resources\Api\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'code'  =>  $this->code,
      'description' =>  $this->description,
    ];
  }
}
