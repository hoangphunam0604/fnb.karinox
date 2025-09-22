<?php

namespace App\Http\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
  public function authorize(): bool
  {
    // Admin requests should be protected by controller middleware / gates
    return true;
  }

  public function rules(): array
  {
    $userId = $this->route('user') ? $this->route('user') : null;
    $rules = [
      'fullname' => ['required', 'string', 'max:255'],
      'username' => ['required', 'string', 'max:100', 'unique:users,username' . ($userId ? ",{$userId},id" : '')],
      'password' => [$this->isMethod('post') ? 'required' : 'sometimes', 'nullable', 'string', 'min:6'],
      'is_active' => ['sometimes', 'boolean'],
      'last_seen_at' => ['nullable', 'date'],
      'role' => ['string', 'in:admin,manager,cashier'],
    ];

    // If role is not admin, branches array is required (user must be attached to one or more branches)
    $role = $this->input('role');
    if ($role && strtolower($role) !== 'admin') {
      $rules['branches'] = ['required', 'array', 'min:1'];
      $rules['branches.*'] = ['integer', 'exists:branches,id'];
    }

    // Always allow branches.* validation when provided as optional
    if (!$role || strtolower($role) === 'admin') {
      // if branches present for admin or unspecified role, validate elements if any
      if ($this->has('branches')) {
        $rules['branches'] = ['sometimes', 'array'];
        $rules['branches.*'] = ['integer', 'exists:branches,id'];
      }
    }

    return $rules;
  }

  public function prepareForValidation(): void
  {
    if ($this->has('is_active')) {
      $this->merge(['is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN)]);
    }

    // normalize: accept 'roles' or 'role' input, prefer single 'role' string
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

    // Normalize branches: accept CSV string or array of ids
    if ($this->has('branches')) {
      $branches = $this->input('branches');
      // If a single numeric value is provided (e.g., branches: 2), convert to array
      if (is_numeric($branches)) {
        $branches = [(int) $branches];
      }
      if (is_string($branches)) {
        $branches = array_values(array_filter(array_map('trim', explode(',', $branches)), fn($v) => $v !== ''));
      }
      if (is_array($branches)) {
        // cast elements to integers
        $branches = array_map(fn($v) => is_numeric($v) ? (int) $v : $v, $branches);
        $this->merge(['branches' => $branches]);
      }
    }
  }
}
