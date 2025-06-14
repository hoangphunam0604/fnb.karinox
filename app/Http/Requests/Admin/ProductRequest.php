<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'code' => 'required|string|max:100|unique:products,code,' . $this->id,
      'name' => 'required|string|max:255',
      'category_id' => 'nullable|exists:categories,id',
      'price' => 'required|numeric|min:0',
      'product_type' => 'required|in:goods,processed,service,combo',
      'allows_sale' => 'boolean',
      'is_reward_point' => 'boolean',
      'is_topping' => 'boolean',
    ];
  }
}
