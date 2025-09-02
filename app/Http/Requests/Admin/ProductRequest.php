<?php

namespace App\Http\Requests\Admin;

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
      // nhóm chung
      'product_group'   => ['nullable', 'integer', 'min:1'],

      // info
      'product_type'    => ['required', Rule::enum(ProductType::class)],
      'category_id'     => ['nullable', 'exists:categories,id'],
      'code'            => ['required', 'string', 'max:100', "unique:products,code,{$id}"],
      'barcode'         => ['nullable', 'string', 'max:255', "unique:products,barcode,{$id}"],
      'name'            => ['required', 'string', 'max:255'],
      'description'     => ['nullable', 'string', 'max:2000'],
      'unit'            => ['nullable', 'string', 'max:50'],
      'status'          => ['nullable', Rule::enum(CommonStatus::class)],

      // giá
      'cost_price'      => ['nullable', 'integer', 'min:0'],
      'regular_price'   => ['nullable', 'integer', 'min:0'],
      'sale_price'      => ['nullable', 'integer', 'min:0', 'lte:regular_price'],

      // flags
      'allows_sale'     => ['boolean'],
      'is_reward_point' => ['boolean'],
      'is_topping'      => ['boolean'],

      'manage_stock'    => ['boolean'],

      'print_label'     => ['boolean'],
      'print_kitchen'   => ['boolean'],

      // media
      'thumbnail'       => ['nullable', 'string', 'max:2048'],

      'manage_stock_branches'  => ['sometimes', 'array'],
      'manage_stock_branches.*' => ['integer', 'exists:branches,id', 'distinct'],

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
      'toppings.*.product_id'            => ['required', 'integer', 'exists:products,id', 'distinct'],
      'toppings.*.extra_price'           => ['nullable', 'integer', 'min:0'],
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
      'manage_stock_branches.array' => 'Danh sách chi nhánh phải là mảng.',
      'manage_stock_branches.*.required' => 'Chi nhánh là bắt buộc.',
      'manage_stock_branches.*.boolean' => 'Chi nhánh phải là true hoặc false.',
      'manage_stock_branches.*.exists' => 'Chi nhánh không tồn tại.',
      'manage_stock_branches.*.distinct' => 'Chi nhánh bị trùng trong danh sách.',

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
      'toppings.*.product_id.required' => 'Sản phẩm topping là bắt buộc.',
      'toppings.*.product_id.integer' => 'Sản phẩm topping phải là số nguyên.',
      'toppings.*.product_id.exists' => 'Sản phẩm topping không tồn tại.',
      'toppings.*.product_id.distinct' => 'Sản phẩm topping bị trùng trong danh sách.',
      'toppings.*.extra_price.integer' => 'Giá thêm topping phải là số nguyên.',
      'toppings.*.extra_price.min' => 'Giá thêm topping không được nhỏ hơn 0.',
    ];
  }
}
