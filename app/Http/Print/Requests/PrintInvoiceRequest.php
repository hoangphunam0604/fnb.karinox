<?php

namespace App\Http\Print\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrintInvoiceRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'order_id' => 'required|integer|exists:orders,id',
      'device_id' => 'nullable|string|max:255'
    ];
  }

  public function messages(): array
  {
    return [
      'order_id.required' => 'ID đơn hàng là bắt buộc',
      'order_id.exists' => 'Đơn hàng không tồn tại',
      'device_id.string' => 'ID thiết bị phải là chuỗi',
      'device_id.max' => 'ID thiết bị không được quá 255 ký tự'
    ];
  }
}
