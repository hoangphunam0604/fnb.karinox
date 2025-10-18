<?php

namespace App\Http\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AreaRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name' => 'required|string|max:255',
      'note' => 'nullable|string|max:500',
      'table_prefix' => 'nullable|string|max:50',
      'table_count' => 'nullable|integer|min:0|max:100',
      'table_capacity' => 'nullable|integer|min:1|max:20',
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'Tên khu vực là bắt buộc',
      'name.max' => 'Tên khu vực không được quá 255 ký tự',
      'note.max' => 'Ghi chú không được quá 500 ký tự',
      'table_prefix.max' => 'Tên gọi phòng/bàn không được quá 50 ký tự',
      'table_count.integer' => 'Số lượng phải là số nguyên',
      'table_count.min' => 'Số lượng phòng/bàn không được nhỏ hơn 0',
      'table_count.max' => 'Số lượng phòng/bàn không được lớn hơn 100',
      'table_capacity.integer' => 'Số ghế phải là số nguyên',
      'table_capacity.min' => 'Số ghế không được nhỏ hơn 1',
      'table_capacity.max' => 'Số ghế không được lớn hơn 20',
    ];
  }
}
