<?php

namespace App\Http\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoucherCampaignRequest extends FormRequest
{
  public function authorize(): bool
  {
    // Authorization handled by route middleware (role:admin|manager)
    return true;
  }

  public function rules(): array
  {
    $campaignId = $this->route('voucherCampaign') ? $this->route('voucherCampaign')->id : null;

    return [
      'name' => 'required|string|max:255',
      'description' => 'nullable|string|max:1000',
      'campaign_type' => 'required|string|in:event,promotion,loyalty,seasonal,birthday,grand_opening',
      // Campaign date range. Support unlimited campaigns via `is_unlimited` flag.
      'start_date' => 'nullable|date',
      // end_date is required unless is_unlimited is true. When provided it must be after or equal to start_date.
      'end_date' => 'nullable|date|after_or_equal:start_date',
      'target_quantity' => 'required|integer|min:1|max:100000',

      // Code generation settings
      'code_prefix' => [
        'required',
        'string',
        'max:20',
        'regex:/^[A-Z0-9]+$/', // Only uppercase letters, numbers, and underscores
        $campaignId
          ? 'unique:voucher_campaigns,code_prefix,' . $campaignId
          : 'unique:voucher_campaigns,code_prefix'
      ],
      'code_format' => 'required|string|max:50',
      'code_length' => 'required|integer|min:4|max:16',

      // Campaign settings
      'is_active' => 'sometimes|boolean',
      'auto_generate' => 'sometimes|boolean',
      'initial_quantity' => 'sometimes|integer|min:1|max:10000|required_if:auto_generate,true',

      // Voucher template validation
      'voucher_template' => 'required|array',
      'voucher_template.description' => 'nullable|string|max:255',
      'voucher_template.voucher_type' => 'required|string|in:standard,membership',
      'voucher_template.discount_type' => 'required|string|in:fixed,percentage',
      'voucher_template.discount_value' => 'required|numeric|min:0',
      'voucher_template.max_discount' => 'nullable|numeric|min:0|required_if:voucher_template.discount_type,percentage',
      'voucher_template.min_order_value' => 'nullable|numeric|min:0',
      'voucher_template.usage_limit' => 'nullable|integer|min:1',
      'voucher_template.per_customer_limit' => 'nullable|integer|min:1',
      'voucher_template.per_customer_daily_limit' => 'nullable|integer|min:1',
      'voucher_template.is_active' => 'sometimes|boolean',
      'voucher_template.disable_holiday' => 'sometimes|boolean',

      // Date overrides for vouchers (optional)
      'voucher_template.start_date' => 'nullable|date',
      'voucher_template.end_date' => 'nullable|date|after_or_equal:voucher_template.start_date',

      // Constraints (optional)
      'voucher_template.applicable_membership_levels' => 'nullable|array',
      'voucher_template.applicable_membership_levels.*' => 'integer|exists:membership_levels,id',
      'voucher_template.valid_days_of_week' => 'nullable|array',
      'voucher_template.valid_days_of_week.*' => 'integer|min:0|max:6',
      'voucher_template.valid_weeks_of_month' => 'nullable|array',
      'voucher_template.valid_weeks_of_month.*' => 'integer|min:1|max:6',
      'voucher_template.valid_months' => 'nullable|array',
      'voucher_template.valid_months.*' => 'integer|min:1|max:12',
      'voucher_template.valid_time_ranges' => 'nullable|array',
      'voucher_template.excluded_dates' => 'nullable|array',
      'voucher_template.excluded_dates.*' => 'date',

      // Branch restrictions (optional)
      'voucher_template.branch_ids' => 'nullable|array',
      'voucher_template.branch_ids.*' => 'integer|exists:branches,id',
    ];
  }

  public function messages(): array
  {
    return [
      'code_prefix.unique' => 'Code prefix already exists. Please choose a different prefix.',
      'code_prefix.regex' => 'Code prefix can only contain uppercase letters, numbers, and underscores.',
      'voucher_template.discount_value.required' => 'Discount value is required.',
      'voucher_template.max_discount.required_if' => 'Maximum discount is required for percentage discount type.',
      'initial_quantity.required_if' => 'Initial quantity is required when auto generate is enabled.',
      'target_quantity.max' => 'Target quantity cannot exceed 100,000 vouchers.',
      'initial_quantity.max' => 'Initial quantity cannot exceed 10,000 vouchers.',
    ];
  }

  public function prepareForValidation(): void
  {
    // Set default code format if not provided
    if (!$this->has('code_format') && $this->has('code_prefix')) {
      $this->merge([
        'code_format' => '{PREFIX}{RANDOM_' . ($this->input('code_length', 8)) . '}'
      ]);
    }

    // Default voucher template values
    if ($this->has('voucher_template')) {
      $template = $this->input('voucher_template');

      // Set defaults
      $template['is_active'] = $template['is_active'] ?? true;
      $template['disable_holiday'] = $template['disable_holiday'] ?? false;
      $template['usage_limit'] = $template['usage_limit'] ?? 1; // Single use by default

      // Convert boolean fields
      foreach (['is_active', 'disable_holiday'] as $field) {
        if (isset($template[$field])) {
          $template[$field] = filter_var($template[$field], FILTER_VALIDATE_BOOLEAN);
        }
      }

      $this->merge(['voucher_template' => $template]);
    }

    // Convert boolean fields
    foreach (['is_active', 'auto_generate', 'is_unlimited'] as $field) {
      if ($this->has($field)) {
        $this->merge([$field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN)]);
      }
    }
  }
}
