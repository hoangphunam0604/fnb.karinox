<?php

namespace App\Http\Admin\Resources;

use App\Enums\TableAndRoomStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableAndRoomResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'        => $this->id,
      'name'      => $this->name,
      'area_id'   => $this->area_id,
      'area'     => new AreaResource($this->area),
      'capacity'  => $this->capacity,
      'note'      => $this->note,
      'status'    => $this->status,
      'status_label' => $this->status->getLabel(),
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
