<?php

namespace App\Http\Print\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPrintRequest extends FormRequest
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
      'status' => 'in:printed,confirmed',
    ];
  }

  public function messages(): array
  {
    return [
      'print_id.required' => 'ID print job là bắt buộc',
      'device_id.required' => 'ID thiết bị là bắt buộc',
      'status.in' => 'Trạng thái phải là printed hoặc confirmed'
    ];
  }
}
