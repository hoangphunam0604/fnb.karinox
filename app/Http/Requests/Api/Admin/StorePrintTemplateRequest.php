<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePrintTemplateRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true; // Nếu cần có thể thêm quyền admin sau
  }

  public function rules(): array
  {
    return [
      'type' => 'required|string',
      'name' => 'required|string',
      'description' => 'nullable|string',
      'content' => 'required|string',
      'is_default' => 'boolean',
      'is_active' => 'boolean',
    ];
  }
}
