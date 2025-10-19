<?php

namespace App\Http\Print\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrintProvisionalRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'order_id' => 'required|integer|exists:orders,id'
    ];
  }

  public function messages(): array
  {
    return [
      'order_id.required' => 'ID đơn hàng là bắt buộc',
      'order_id.exists' => 'Đơn hàng không tồn tại'
    ];
  }
}
