<?php

namespace App\Services;

use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
    $key = 'holiday:isHoliday:' . $date->toDateString();
    // cache result for 24 hours to reduce DB lookups; invalidate manually when holidays change
    return Cache::remember($key, now()->addDay(), function () use ($date) {
      return Holiday::forDate($date)->exists();
    });
  }
}
