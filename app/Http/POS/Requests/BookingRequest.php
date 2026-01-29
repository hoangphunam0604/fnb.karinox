<?php

namespace App\Http\POS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'table_id' => ['required', 'integer', 'exists:tables_and_rooms,id'],
      'name' => ['nullable', 'string', 'max:255'],
      'start_time' => ['required', 'date'],
      'end_time' => ['required', 'date', 'after:start_time'],
    ];
  }

  public function messages(): array
  {
    return [
      'table_id.required' => 'Vui lòng chọn sân.',
      'table_id.exists' => 'Sân không tồn tại.',

      'name.max' => 'Tên booking không được vượt quá 255 ký tự.',

      'start_time.required' => 'Thời gian bắt đầu là bắt buộc.',
      'start_time.date' => 'Thời gian bắt đầu không hợp lệ.',

      'end_time.required' => 'Thời gian kết thúc là bắt buộc.',
      'end_time.date' => 'Thời gian kết thúc không hợp lệ.',
      'end_time.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
    ];
  }
}
