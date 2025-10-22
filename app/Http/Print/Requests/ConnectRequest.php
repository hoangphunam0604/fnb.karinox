<?php

namespace App\Http\Print\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConnectRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'connection_code' => 'required|string|size:12'
    ];
  }

  public function messages(): array
  {
    return [
      'connection_code.required' => 'Mã kết nối là bắt buộc',
      'connection_code.size' => 'Mã kết nối phải có đúng 12 ký tự'
    ];
  }
}
