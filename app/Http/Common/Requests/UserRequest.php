<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
  public function authorize(): bool
  {
    // Nếu muốn kiểm soát quyền, thay bằng logic kiểm tra quyền admin ở đây
    return true;
  }

  public function rules(): array
  {
    $userId = $this->route('user') ? $this->route('user')->id : null;

    return [
      'fullname' => ['required', 'string', 'max:255'],
      'username' => ['required', 'string', 'max:100', 'unique:users,username' . ($userId ? ",{$userId},id" : '')],
      'password' => [$this->isMethod('post') ? 'required' : 'sometimes', 'nullable', 'string', 'min:6'],
      'is_active' => ['sometimes', 'boolean'],
      'current_branch' => ['nullable', 'exists:branches,id'],
      'last_seen_at' => ['nullable', 'date'],
      'role' => ['sometimes', 'nullable', 'string', 'in:admin,manager,cashier'],
    ];
  }

  public function prepareForValidation(): void
  {
    if ($this->has('is_active')) {
      $this->merge(['is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN)]);
    }
    if ($this->has('roles')) {
      $roles = $this->input('roles');
      if (is_string($roles)) {
        $roles = array_values(array_filter(array_map('trim', explode(',', $roles))));
      }
      if (is_array($roles) && count($roles) > 0) {
        $this->merge(['role' => (string) $roles[0]]);
      }
    }

    if ($this->has('role')) {
      $role = $this->input('role');
      if (is_string($role)) {
        $this->merge(['role' => trim($role)]);
      }
    }
  }
}
