<?php

namespace App\Http\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name'         => 'required|string|max:255',
      'email'        => 'nullable|email|max:255',
      'phone_number' => 'nullable|string|max:20',
      'address'      => 'nullable|string|max:500',
    ];
  }
}
