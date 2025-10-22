<?php

namespace App\Http\Print\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrintHistoryRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'device_id' => 'nullable|string',
      'branch_id' => 'nullable|integer|exists:branches,id',
      'status' => 'nullable|in:requested,printed,confirmed,failed',
      'from_date' => 'nullable|date',
      'to_date' => 'nullable|date|after_or_equal:from_date',
      'per_page' => 'nullable|integer|min:1|max:100'
    ];
  }

  public function messages(): array
  {
    return [
      'branch_id.exists' => 'Chi nhánh không tồn tại',
      'status.in' => 'Trạng thái không hợp lệ',
      'to_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu',
      'per_page.max' => 'Số lượng bản ghi tối đa là 100'
    ];
  }
}
