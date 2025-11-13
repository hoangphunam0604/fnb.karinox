<?php

namespace App\Http\PaymentGateway\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'order_code' => 'required|string|max:255'
    ];
  }

  public function messages(): array
  {
    return [
      'order_code.required' => 'Chưa có mã đơn hàng',
      'order_code.string' => 'Mã đơn hàng không hợp lệ',
      'order_code.max' => 'Mã đơn hàng không hợp lệ',
    ];
  }
}
