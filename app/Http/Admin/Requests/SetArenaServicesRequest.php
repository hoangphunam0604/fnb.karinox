<?php

namespace App\Http\Admin\Requests;

use App\Enums\ProductArenaType;
use Illuminate\Foundation\Http\FormRequest;

class SetArenaServicesRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'services' => ['required', 'array'],
      'services.*.arena_type' => ['required', 'string', 'in:' . implode(',', ProductArenaType::casesAsArray())],
      'services.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
    ];
  }

  public function messages(): array
  {
    return [
      'services.required' => 'Danh sách dịch vụ là bắt buộc.',
      'services.array' => 'Danh sách dịch vụ phải là mảng.',

      'services.*.arena_type.required' => 'Loại dịch vụ Arena là bắt buộc.',
      'services.*.arena_type.string' => 'Loại dịch vụ Arena phải là chuỗi.',
      'services.*.arena_type.in' => 'Loại dịch vụ Arena không hợp lệ.',

      'services.*.product_id.integer' => 'ID sản phẩm phải là số nguyên.',
      'services.*.product_id.exists' => 'Sản phẩm không tồn tại.',
    ];
  }
}
