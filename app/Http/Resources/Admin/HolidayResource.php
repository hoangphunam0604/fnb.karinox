<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HolidayResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'description' => $this->description,
      'calendar' => $this->calendar,
      'year' => $this->year,
      'month' => $this->month,
      'day' => $this->day,
      'is_recurring' => (bool) $this->is_recurring,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
