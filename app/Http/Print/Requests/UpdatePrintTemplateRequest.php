<?php

namespace App\Http\Print\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrintTemplateRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name' => 'sometimes|required|string|max:255',
      'type' => 'sometimes|required|string|in:provisional,invoice,labels,kitchen',
      'content' => 'sometimes|required|string',
      'variables' => 'nullable|json',
      'is_active' => 'nullable|boolean',
      'is_default' => 'nullable|boolean',
      'description' => 'nullable|string|max:500'
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'Tên template là bắt buộc',
      'name.max' => 'Tên template không được quá 255 ký tự',
      'type.required' => 'Loại template là bắt buộc',
      'type.in' => 'Loại template không hợp lệ. Chỉ chấp nhận: provisional, invoice, labels, kitchen',
      'content.required' => 'Nội dung template là bắt buộc',
      'variables.json' => 'Variables phải là JSON hợp lệ',
      'description.max' => 'Mô tả không được quá 500 ký tự'
    ];
  }
}
