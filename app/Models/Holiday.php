<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class Holiday extends Model
{
  protected $fillable = [
    'name',
    'date',
    'is_lunar',
    'description',
    'is_recurring',
  ];

  protected $casts = [
    'date' => 'date',
    'is_lunar' => 'boolean',
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
    $dateStr = $date->toDateString();
    $month = $date->format('m');
    $day = $date->format('d');

    return $query->where(function ($q) use ($dateStr, $month, $day) {
      // exact match (year included)
      $q->whereDate('date', $dateStr)
        // or recurring entries that repeat every year (match month/day)
        ->orWhere(function ($q2) use ($month, $day) {
          $q2->where('is_recurring', true)
            ->whereMonth('date', $month)
            ->whereDay('date', $day);
        });
    });
  }
}
