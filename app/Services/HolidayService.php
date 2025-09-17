<?php

namespace App\Services;

use App\Models\Holiday;
use Illuminate\Support\Carbon;

class HolidayService
{
  public function getAll(array $filters = [])
  {
    $query = Holiday::query();

    if (!empty($filters['year'])) {
      $query->whereYear('date', $filters['year']);
    }
    return $query->orderBy('date')->get();
  }

  public function getPaginated(array $filters = [], int $perPage = 20)
  {
    $query = Holiday::query();

    if (!empty($filters['year'])) {
      $query->whereYear('date', $filters['year']);
    }

    return $query->orderBy('date')->paginate($perPage);
  }

  public function getById(int $id): ?Holiday
  {
    return Holiday::find($id);
  }

  public function create(array $data): Holiday
  {
    return Holiday::create($data);
  }

  public function update(int $id, array $data): ?Holiday
  {
    $holiday = Holiday::find($id);
    if ($holiday) {
      $holiday->update($data);
    }
    return $holiday;
  }

  public function delete(int $id): bool
  {
    return Holiday::where('id', $id)->delete() > 0;
  }

  public function isHoliday(?Carbon $date = null): bool
  {
    $date = $date ?? now();
    return Holiday::forDate($date)->exists();
  }
}
