<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PermissionRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    $permId = $this->route('permission') ? $this->route('permission')->id : null;

    return [
      'name' => ['required', 'string', 'max:255', 'unique:permissions,name' . ($permId ? ",{$permId}" : '')],
      'guard_name' => ['sometimes', 'string', 'max:50'],
    ];
  }
}
