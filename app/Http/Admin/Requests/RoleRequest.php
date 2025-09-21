<?php

namespace App\Http\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    $roleId = $this->route('role') ? $this->route('role')->id : null;

    return [
      'name' => ['required', 'string', 'max:255', 'unique:roles,name' . ($roleId ? ",{$roleId}" : '')],
      'permissions' => ['sometimes', 'array'],
      'permissions.*' => ['integer', 'exists:permissions,id'],
    ];
  }
}
