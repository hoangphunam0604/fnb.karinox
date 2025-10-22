<?php

namespace App\Http\Admin\Resources;

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
      'print_connection_code' => $this->print_connection_code,
      'status'      => $this->status,
      'sort_order'      => $this->sort_order,
      'created_at'   => $this->created_at,
      'updated_at'   => $this->updated_at,
    ];
  }
}
