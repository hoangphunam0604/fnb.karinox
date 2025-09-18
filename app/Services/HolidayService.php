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

  protected function applySearch($query, array $params)
  {
    if (!empty($params['keyword'])):
      $keyword = $params['keyword'];
      $query->where('name', 'LIKE', '%' . $keyword . '%');
    endif;

    if (!empty($params['calendar']))
      $query->where('calendar', $params['calendar']);
    // If caller specified a year, don't filter by year here (getList handles it).
    if (empty($params['year'])) {
      $currentYear = (int) date('Y');
      $query->where(function ($q) use ($currentYear) {
        $q->whereNull('year')
          ->orWhere(function ($q2) use ($currentYear) {
            $q2->whereNotNull('year')
              ->where('year', '>=', $currentYear);
          });
      });
    }
    return $query;
  }

  protected function orderBy($query, $params)
  {
    $query->orderByRaw('(year IS NOT NULL) asc')->orderBy('year', 'asc');
    // Always order by month/day to get chronological order within a year.
    $query->orderBy('month', 'asc')->orderBy('day', 'asc');

    /*     // If a year is provided, we don't need extra ordering by year.
    if (!empty($params['year'])) {
      return $query;
    }

    // When no specific year requested, put recurring holidays (year IS NULL) first,
    // then dated holidays in ascending year order. */
    return $query;
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
