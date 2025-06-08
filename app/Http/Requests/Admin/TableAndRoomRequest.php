<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TableAndRoomRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name'      => 'required|string|max:255',
      'area_id'   => 'nullable|exists:areas,id',
      'capacity'  => 'nullable|integer|min:1',
      'note'      => 'nullable|string|max:500',
      'status'    => 'nullable|string|max:50', // bạn có thể đổi sang in:active,inactive,... nếu cần
    ];
  }
}
