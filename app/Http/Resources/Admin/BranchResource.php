<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'           => $this->id,
      'name'         => $this->name,
      'email'        => $this->email,
      'phone_number' => $this->phone_number,
      'address'      => $this->address,
      'created_at'   => $this->created_at,
      'updated_at'   => $this->updated_at,
    ];
  }
}
