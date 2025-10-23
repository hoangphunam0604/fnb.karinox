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
      'print_id' => 'required|string'
    ];
  }

  public function messages(): array
  {
    return [
      'print_id.required' => 'ID print job là bắt buộc'
    ];
  }
}
