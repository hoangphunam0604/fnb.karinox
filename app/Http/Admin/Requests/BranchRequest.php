<?php

namespace App\Http\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name'                  => 'required|string|max:255',
      'email'                 => 'nullable|email|max:255',
      'phone_number'          => 'nullable|string|max:20',
      'address'               => 'nullable|string|max:500',
      'connection_code' => 'nullable|string|min:3|max:50|alpha_num|unique:branches,connection_code,' . $this->route('branch'),
    ];
  }

  public function messages(): array
  {
    return [
      'connection_code.min'       => 'Mã kết nối phải có ít nhất 3 ký tự.',
      'connection_code.max'       => 'Mã kết nối không được vượt quá 50 ký tự.',
      'connection_code.alpha_num' => 'Mã kết nối chỉ được chứa chữ cái và số.',
      'connection_code.unique'    => 'Mã kết nối này đã được sử dụng.',
    ];
  }
}
