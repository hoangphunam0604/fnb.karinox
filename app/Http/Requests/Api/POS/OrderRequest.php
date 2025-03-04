<?php

namespace App\Http\Requests\Api\POS;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'order_items' => 'array',
      'order_items.*.product_id' => 'required|integer',
      'order_items.*.quantity' => 'required|integer|min:1',
      'order_items.*.toppings' => 'array',
      'order_items.*.toppings.*.product_id' => 'required|integer',
      'voucher_code' => 'nullable|string',
      'note' => 'nullable|string',
      'table_id' => 'nullable|integer',
      'status' => 'nullable|in:pending,confirmed,canceled'
    ];
  }

  public function messages(): array
  {
    return [
      'order_items.array' => 'Danh sách sản phẩm không hợp lệ.',
      'order_items.*.product_id.required' => 'Vui lòng chọn sản phẩm.',
      'order_items.*.product_id.integer' => 'ID sản phẩm không hợp lệ.',
      'order_items.*.quantity.required' => 'Vui lòng nhập số lượng sản phẩm.',
      'order_items.*.quantity.integer' => 'Số lượng sản phẩm phải là số nguyên.',
      'order_items.*.quantity.min' => 'Số lượng sản phẩm tối thiểu là 1.',
      'order_items.*.toppings.array' => 'Topping phải là một danh sách.',
      'order_items.*.toppings.*.product_id.required' => 'Vui lòng chọn topping.',
      'voucher_code.string' => 'Mã giảm giá không hợp lệ.',
      'note.string' => 'Ghi chú không hợp lệ.',
      'table_id.integer' => 'Bàn/phòng không hợp lệ.',
      'status.in' => 'Trạng thái đơn hàng không hợp lệ. Chỉ chấp nhận các giá trị: pending, confirmed, canceled.',
    ];
  }
}
