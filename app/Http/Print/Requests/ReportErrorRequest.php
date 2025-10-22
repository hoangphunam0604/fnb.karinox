<?php

namespace App\Http\Print\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportErrorRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'print_id' => 'required|string',
      'device_id' => 'required|string',
      'error_type' => 'required|string|max:50',
      'error_message' => 'required|string|max:1000',
      'error_details' => 'nullable|array'
    ];
  }

  public function messages(): array
  {
    return [
      'print_id.required' => 'ID print job là bắt buộc',
      'device_id.required' => 'ID thiết bị là bắt buộc',
      'error_type.required' => 'Loại lỗi là bắt buộc',
      'error_message.required' => 'Thông báo lỗi là bắt buộc',
      'error_message.max' => 'Thông báo lỗi không được vượt quá 1000 ký tự'
    ];
  }
}
