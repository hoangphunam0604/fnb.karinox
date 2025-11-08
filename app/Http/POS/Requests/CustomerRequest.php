<?php

namespace App\Http\POS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    $id = $this->route('customer'); // nếu là PUT / PATCH
    return [
      'loyalty_card_number' => ['required', 'string', 'max:255'],
      'fullname' => ['required', 'string', 'max:255'],
      'email'    => ['nullable', 'email', 'max:255', 'unique:customers,email,' . $id],
      'phone'    => ['required', 'string', 'max:20', 'unique:customers,phone,' . $id],
      'address'  => ['nullable', 'string', 'max:255'],
      'birthday' => ['nullable', 'date'],
      'gender'   => ['required', 'in:male,female'],
    ];
  }

  public function messages(): array
  {
    return [
      'loyalty_card_number.required' => 'Họ tên không được để trống.',
      'fullname.required' => 'Họ tên không được để trống.',
      'email.unique'      => 'Địa chỉ email đã tồn tại.',
      'phone.required'    => 'Số điện thoại là bắt buộc.',
      'phone.unique'      => 'Số điện thoại đã tồn tại.',
      'gender.in'         => 'Giới tính không hợp lệ.',
    ];
  }
}
