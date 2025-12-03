<?php

namespace App\Http\Admin\Requests;

use App\Enums\CommonStatus;
use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    $id = $this->id;
    return [
      'menu_id'     => ['nullable', 'exists:menus,id'],
      'allows_sale'     => ['boolean'],
      'is_reward_point' => ['boolean'],
      'print_label'     => ['boolean'],
      'print_kitchen'   => ['boolean'],

      // media
      'thumbnail'       => ['nullable', 'string', 'max:2048'],


      // ====== RELATION: BRANCHES (pivot: is_selling stock_quantity) ======
      // branches: [{ branch_id: 5, is_selling: true }, ...]
      'branches'                           => ['sometimes', 'array'],
      'branches.*.branch_id'               => ['required', 'integer', 'exists:branches,id', 'distinct'],
      'branches.*.is_selling'              => ['required', 'boolean'],/* 

      // ====== RELATION: ATTRIBUTES (pivot: value) ======
      // attributes: [{ attribute_id: 5, value: "Size L" }, ...]
      'attributes'                       => ['sometimes', 'array'],
      'attributes.*.attribute_id'        => ['required', 'integer', 'exists:attributes,id', 'distinct'],
      'attributes.*.value'               => ['nullable', 'string', 'max:255'],

      // ====== RELATION: FORMULAS (thành phần / nguyên liệu) ======
      // formulas: [{ ingredient_id: 12, quantity: 1.5, unit: "g" }, ...]
      // ingredient_id là product (nguyên liệu/hàng khác)
      'formulas'                         => ['sometimes', 'array'],
      'formulas.*.ingredient_id'         => ['required', 'integer', 'exists:products,id'],
      'formulas.*.quantity'              => ['required', 'numeric', 'min:0.0001'],
      'formulas.*.unit'                  => ['nullable', 'string', 'max:50'],

      // ====== RELATION: TOPPINGS ======
      // toppings: [{ product_id: 99, extra_price: 5000 }, ...]
      'toppings'                         => ['sometimes', 'array'],
      'toppings.*.topping_id'            => ['required', 'integer', 'exists:products,id', 'distinct'] */
    ];
  }

  public function messages(): array
  {
    return [
      // ====== BASIC FIELDS ======
      'product_group.integer' => 'Nhóm sản phẩm phải là số nguyên.',
      'product_group.min' => 'Nhóm sản phẩm phải lớn hơn hoặc bằng 1.',

      'product_type.required' => 'Loại sản phẩm là bắt buộc.',
      'product_type.in' => 'Loại sản phẩm không hợp lệ.',

      'category_id.integer' => 'Danh mục sản phẩm không hợp lệ.',
      'category_id.exists' => 'Danh mục sản phẩm không tồn tại.',

      'code.required' => 'Mã sản phẩm là bắt buộc.',
      'code.string' => 'Mã sản phẩm phải là chuỗi.',
      'code.max' => 'Mã sản phẩm không được vượt quá 100 ký tự.',
      'code.unique' => 'Mã sản phẩm đã tồn tại.',

      'barcode.string' => 'Barcode phải là chuỗi.',
      'barcode.max' => 'Barcode không được vượt quá 255 ký tự.',
      'barcode.unique' => 'Barcode đã tồn tại.',

      'name.required' => 'Tên sản phẩm là bắt buộc.',
      'name.string' => 'Tên sản phẩm phải là chuỗi.',
      'name.max' => 'Tên sản phẩm không được vượt quá 255 ký tự.',

      'description.string' => 'Mô tả phải là chuỗi.',
      'description.max' => 'Mô tả không được vượt quá 2000 ký tự.',

      'unit.string' => 'Đơn vị phải là chuỗi.',
      'unit.max' => 'Đơn vị không được vượt quá 50 ký tự.',

      'status.in' => 'Trạng thái sản phẩm không hợp lệ.',

      'cost_price.integer' => 'Giá nhập phải là số nguyên.',
      'cost_price.min' => 'Giá nhập phải lớn hơn hoặc bằng 0.',

      'regular_price.integer' => 'Giá bán phải là số nguyên.',
      'regular_price.min' => 'Giá bán phải lớn hơn hoặc bằng 0.',

      'sale_price.integer' => 'Giá khuyến mãi phải là số nguyên.',
      'sale_price.min' => 'Giá khuyến mãi phải lớn hơn hoặc bằng 0.',
      'sale_price.lte' => 'Giá khuyến mãi phải nhỏ hơn hoặc bằng giá bán.',

      'allows_sale.boolean' => 'Trường "bán trực tiếp" phải là true hoặc false.',
      'is_reward_point.boolean' => 'Trường "tích điểm" phải là true hoặc false.',
      'is_topping.boolean' => 'Trường "topping" phải là true hoặc false.',
      'manage_stock.boolean' => 'Trường "quản lý tồn kho" phải là true hoặc false.',
      'print_label.boolean' => 'Trường "in tem" phải là true hoặc false.',
      'print_kitchen.boolean' => 'Trường "in bếp" phải là true hoặc false.',

      'thumbnail.string' => 'Ảnh đại diện phải là đường dẫn hợp lệ.',
      'thumbnail.max' => 'Ảnh đại diện không được vượt quá 2048 ký tự.',

      'images.array' => 'Danh sách hình ảnh phải là mảng.',
      'images.*.string' => 'Mỗi hình ảnh phải là đường dẫn hợp lệ.',
      'images.*.max' => 'Mỗi hình ảnh không được vượt quá 2048 ký tự.',

      // ====== RELATION: BRANCHES ======
      'branches.array' => 'Danh sách chi nhánh phải là một mảng.',

      'branches.*.branch_id.required' => 'Mã chi nhánh là bắt buộc.',
      'branches.*.branch_id.integer'  => 'Mã chi nhánh phải là số nguyên.',
      'branches.*.branch_id.exists'   => 'Chi nhánh không tồn tại trong hệ thống.',
      'branches.*.branch_id.distinct' => 'Chi nhánh bị trùng trong danh sách.',

      'branches.*.is_selling.required' => 'Trạng thái bán tại chi nhánh là bắt buộc.',
      'branches.*.is_selling.boolean'  => 'Trạng thái bán tại chi nhánh phải là true hoặc false.',


      // ====== RELATION: ATTRIBUTES ======
      'attributes.array' => 'Danh sách thuộc tính phải là mảng.',
      'attributes.*.attribute_id.required' => 'Thuộc tính là bắt buộc.',
      'attributes.*.attribute_id.integer' => 'Thuộc tính phải là số nguyên.',
      'attributes.*.attribute_id.exists' => 'Thuộc tính không tồn tại.',
      'attributes.*.attribute_id.distinct' => 'Thuộc tính bị trùng trong danh sách.',
      'attributes.*.value.string' => 'Giá trị thuộc tính phải là chuỗi.',
      'attributes.*.value.max' => 'Giá trị thuộc tính không được vượt quá 255 ký tự.',

      // ====== RELATION: FORMULAS ======
      'formulas.array' => 'Danh sách công thức phải là mảng.',
      'formulas.*.ingredient_id.required' => 'Nguyên liệu là bắt buộc.',
      'formulas.*.ingredient_id.integer' => 'Nguyên liệu phải là số nguyên.',
      'formulas.*.ingredient_id.exists' => 'Nguyên liệu không tồn tại.',
      'formulas.*.quantity.required' => 'Số lượng nguyên liệu là bắt buộc.',
      'formulas.*.quantity.numeric' => 'Số lượng nguyên liệu phải là số.',
      'formulas.*.quantity.min' => 'Số lượng nguyên liệu phải lớn hơn 0.',
      'formulas.*.unit.string' => 'Đơn vị nguyên liệu phải là chuỗi.',
      'formulas.*.unit.max' => 'Đơn vị nguyên liệu không được vượt quá 50 ký tự.',

      // ====== RELATION: TOPPINGS ======
      'toppings.array' => 'Danh sách topping phải là mảng.',
      'toppings.*.topping_id.required' => 'Sản phẩm topping là bắt buộc.',
      'toppings.*.topping_id.integer' => 'Sản phẩm topping phải là số nguyên.',
      'toppings.*.topping_id.exists' => 'Sản phẩm topping không tồn tại.',
      'toppings.*.topping_id.distinct' => 'Sản phẩm topping bị trùng trong danh sách.',
    ];
  }
}
