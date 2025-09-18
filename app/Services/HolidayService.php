<?php

namespace App\Services;

use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;

class HolidayService extends BaseService
{
  protected function model(): Model
  {
    return new Holiday();
  }

  /**
   * Helper to check if a given date is a holiday.
   */
  public function isHoliday(?Carbon $date = null): bool
  {
    $date = $date ?? now();
    return Holiday::forDate($date)->exists();
  }
}
