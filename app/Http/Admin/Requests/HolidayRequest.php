<?php

namespace App\Http\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class HolidayRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name' => ['required', 'string', 'max:255'],
      'date' => ['required', 'date'], // single date input from frontend
      'calendar' => ['nullable', 'in:solar,lunar'],
      'description' => ['nullable', 'string'],
      'is_recurring' => ['sometimes', 'boolean'],
    ];
  }

  protected function prepareForValidation(): void
  {
    if ($this->filled('date')) {
      try {
        $d = Carbon::parse($this->input('date'));
        $this->merge([
          'year' => $d->year,
          'month' => $d->month,
          'day' => $d->day,
        ]);
      } catch (\Exception $e) {
        // let validation handle invalid date
      }
    }
  }

  /**
   * Return only the data the controller should use to create/update the model.
   */
  public function validatedData(): array
  {
    $data = $this->validated();
    // ensure month/day/year are present when date provided
    if ($this->filled('date')) {
      $data['year'] = $this->input('year');
      $data['month'] = $this->input('month');
      $data['day'] = $this->input('day');
    }
    // default calendar
    $data['calendar'] = $data['calendar'] ?? 'solar';
    // default is_recurring true unless explicit false provided
    $data['is_recurring'] = isset($data['is_recurring']) ? (bool)$data['is_recurring'] : true;

    return $data;
  }
}
