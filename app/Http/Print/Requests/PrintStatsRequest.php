<?php

namespace App\Http\Print\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrintStatsRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'branch_id' => 'nullable|integer|exists:branches,id',
      'device_id' => 'nullable|string',
      'date' => 'nullable|date',
      'period' => 'nullable|in:today,yesterday,week,month'
    ];
  }

  public function messages(): array
  {
    return [
      'branch_id.exists' => 'Chi nhánh không tồn tại',
      'period.in' => 'Khoảng thời gian phải là: today, yesterday, week, month'
    ];
  }
}
