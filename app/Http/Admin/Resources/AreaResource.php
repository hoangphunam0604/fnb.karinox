<?php

namespace App\Http\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'note' => $this->note,
      'branch_id' => $this->branch_id,
      'tables_count' => $this->when(
        $this->relationLoaded('tablesAndRooms'),
        $this->tablesAndRooms->count()
      ),
      'tables_and_rooms' => $this->when(
        $this->relationLoaded('tablesAndRooms'),
        function () {
          return $this->tablesAndRooms->map(function ($table) {
            return [
              'id' => $table->id,
              'name' => $table->name,
              'capacity' => $table->capacity,
              'status' => $table->status,
              'note' => $table->note,
            ];
          });
        }
      ),
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
