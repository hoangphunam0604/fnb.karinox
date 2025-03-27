<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrintTemplateRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
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
