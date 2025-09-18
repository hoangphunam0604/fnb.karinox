<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class Holiday extends Model
{
  protected $fillable = [
    'name',
    'description',
    'calendar',
    'year',
    'month',
    'day',
    'is_recurring',
  ];

  protected $casts = [
    'year' => 'integer',
    'month' => 'integer',
    'day' => 'integer',
    'is_recurring' => 'boolean',
  ];

  /**
   * Scope to find holidays that match a given date.
   * - Exact date match (covers manually-added lunar holidays and non-recurring entries)
   * - Recurring holidays: match by month/day when `is_recurring` is true (ignores year)
   *
   * Usage: Holiday::forDate($date)->get();
   *
   * @param  Builder  $query
   * @param  Carbon|string  $date
   * @return Builder
   */
  public function scopeForDate(Builder $query, $date): Builder
  {
    $date = $date instanceof Carbon ? $date : Carbon::parse($date);
    $year = (int) $date->year;
    $month = (int) $date->month;
    $day = (int) $date->day;

    return $query->where(function ($q) use ($year, $month, $day) {
      // exact (non-recurring) holidays must match year/month/day
      $q->where(function ($q1) use ($year, $month, $day) {
        $q1->where('is_recurring', false)
          ->where('year', $year)
          ->where('month', $month)
          ->where('day', $day);
      })
        // or recurring holidays that match by month/day (ignore year)
        ->orWhere(function ($q2) use ($month, $day) {
          $q2->where('is_recurring', true)
            ->where('month', $month)
            ->where('day', $day);
        });
    });
  }
}
