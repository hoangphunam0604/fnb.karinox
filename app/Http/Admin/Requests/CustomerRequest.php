<?php

namespace App\Http\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true; // tuỳ chỉnh thêm gate/permission nếu cần
  }

  public function rules(): array
  {
    return [
      'loyalty_card_number' => ['nullable', 'string', 'max:191'],
      'fullname' => ['required', 'string', 'max:191'],
      'email' => ['nullable', 'email', 'max:191'],
      'avatar' => ['nullable', 'string'],
      'phone' => ['nullable', 'string', 'max:32'],
      'address' => ['nullable', 'string', 'max:255'],
      'birthday' => ['nullable', 'date'],
      'gender' => ['nullable', 'in:male,female'],
      'status' => ['required', 'in:active,inactive'],
      'membership_level_id' => ['nullable', 'exists:membership_levels,id'],
      'company_name' => ['nullable', 'string'],
      'tax_id' => ['nullable', 'string'],
      'note' => ['nullable', 'string'],
      'arena_member' => ['nullable'],
      'arena_member_exp' => ['nullable'],
    ];
  }
  public function messages(): array
  {
    return [
      'fullname.required' => 'Vui lòng nhập họ tên khách hàng.',
      'fullname.max' => 'Họ tên không được vượt quá 191 ký tự.',

      'email.email' => 'Địa chỉ email không hợp lệ.',
      'email.max' => 'Email không được vượt quá 191 ký tự.',

      'phone.max' => 'Số điện thoại không được vượt quá 32 ký tự.',

      'address.max' => 'Địa chỉ không được vượt quá 255 ký tự.',

      'birthday.date' => 'Ngày sinh không hợp lệ.',

      'gender.in' => 'Giới tính phải là male, female hoặc other.',

      'membership_level_id.exists' => 'Hạng thành viên không tồn tại.',

      'loyalty_card_number.max' => 'Mã thẻ khách hàng không được vượt quá 191 ký tự.',
    ];
  }
}
