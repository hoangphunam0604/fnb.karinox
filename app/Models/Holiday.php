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

  // Always append computed attributes we want in array/json form
  protected $appends = [
    'is_passed',
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

  /**
   * Return true if this holiday is already passed relative to the given date (or now).
   * For recurring holidays, compares month/day in the reference year.
   * For non-recurring holidays, compares the full year/month/day.
   *
   * @param \Illuminate\Support\Carbon|string|null $reference
   * @return bool
   */
  public function isPassed($reference = null): bool
  {
    $ref = $reference instanceof Carbon ? $reference : Carbon::parse($reference ?? now());

    // determine holiday's effective date
    if ($this->is_recurring) {
      // recurring: use current/ref year
      $holidayDate = Carbon::create($ref->year, $this->month ?? 1, $this->day ?? 1)->startOfDay();
    } else {
      // non-recurring: need year/month/day
      if ($this->year) {
        $holidayDate = Carbon::create($this->year, $this->month ?? 1, $this->day ?? 1)->startOfDay();
      } elseif ($this->getAttribute('date')) {
        $holidayDate = Carbon::parse($this->getAttribute('date'))->startOfDay();
      } else {
        // fallback: treat as not passed
        return false;
      }
    }

    return $holidayDate->lessThanOrEqualTo($ref->startOfDay());
  }

  /**
   * Accessor for `is_passed` appended attribute
   */
  public function getIsPassedAttribute(): bool
  {
    return $this->isPassed();
  }
}
