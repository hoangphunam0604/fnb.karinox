<?php

namespace App\Http\Print\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestPrintRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'print_type' => 'required|string|in:provisional,invoice,labels,kitchen',
      'template_id' => 'nullable|integer|exists:print_templates,id',
      'mock_data_type' => 'nullable|string|in:simple,complex,with_toppings,large_order'
    ];
  }

  public function messages(): array
  {
    return [
      'print_type.required' => 'Loại in là bắt buộc',
      'print_type.in' => 'Loại in không hợp lệ. Chỉ chấp nhận: provisional, invoice, labels, kitchen',
      'template_id.exists' => 'Template không tồn tại',
      'device_id.max' => 'ID thiết bị không được quá 255 ký tự',
      'mock_data_type.in' => 'Loại dữ liệu mẫu không hợp lệ'
    ];
  }
}
