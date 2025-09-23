<?php

namespace App\Http\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherCampaignResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'description' => $this->description,
      'campaign_type' => $this->campaign_type,
      'campaign_type_label' => $this->getCampaignTypeLabel(),

      // Dates
      'start_date' => $this->start_date?->format('Y-m-d H:i:s'),
      'end_date' => $this->end_date?->format('Y-m-d H:i:s'),
      'start_date_formatted' => $this->start_date?->format('d/m/Y H:i'),
      'end_date_formatted' => $this->end_date?->format('d/m/Y H:i'),

      // Quantities and stats
      'target_quantity' => $this->target_quantity,
      'generated_quantity' => $this->generated_quantity,
      'used_quantity' => $this->used_quantity,
      'remaining_quantity' => $this->getRemainingQuantity(),

      // Calculated stats
      'usage_rate' => $this->getUsageRate(),
      'generation_rate' => $this->getGenerationRate(),
      'days_remaining' => $this->getDaysRemaining(),

      // Code generation settings
      'code_prefix' => $this->code_prefix,
      'code_format' => $this->code_format,
      'code_length' => $this->code_length,

      // Status
      'is_active' => $this->is_active,
      'auto_generate' => $this->auto_generate,
      'is_campaign_active' => $this->isActive(),
      'can_generate_more' => $this->canGenerateMore(),

      // Voucher template
      'voucher_template' => $this->formatVoucherTemplate(),

      // Relationships
      'creator' => $this->whenLoaded('creator', function () {
        return [
          'id' => $this->creator->id,
          'fullname' => $this->creator->fullname,
          'username' => $this->creator->username,
        ];
      }),

      'vouchers_count' => $this->whenCounted('vouchers'),
      'vouchers' => $this->whenLoaded('vouchers'),

      // Timestamps
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
      'created_at_formatted' => $this->created_at?->format('d/m/Y H:i'),
      'updated_at_formatted' => $this->updated_at?->format('d/m/Y H:i'),
    ];
  }

  private function getCampaignTypeLabel(): string
  {
    return match ($this->campaign_type) {
      'event' => 'Sự kiện',
      'promotion' => 'Khuyến mại',
      'loyalty' => 'Khách hàng thân thiết',
      'seasonal' => 'Theo mùa',
      'birthday' => 'Sinh nhật',
      'grand_opening' => 'Khai trương',
      default => ucfirst($this->campaign_type),
    };
  }

  private function formatVoucherTemplate(): array
  {
    $template = $this->voucher_template ?? [];

    // Add formatted discount information
    if (isset($template['discount_type']) && isset($template['discount_value'])) {
      $template['discount_formatted'] = $this->formatDiscount(
        $template['discount_type'],
        $template['discount_value'],
        $template['max_discount'] ?? null
      );
    }

    // Add formatted constraints
    $template['constraints'] = $this->formatConstraints($template);

    // Add branch information if present
    if (isset($template['branch_ids']) && is_array($template['branch_ids'])) {
      $template['branch_count'] = count($template['branch_ids']);
      $template['is_branch_specific'] = true;
    } else {
      $template['branch_count'] = 0;
      $template['is_branch_specific'] = false;
    }

    return $template;
  }

  private function formatDiscount(string $type, float $value, ?float $maxDiscount = null): string
  {
    if ($type === 'percentage') {
      $formatted = number_format($value, 1) . '%';
      if ($maxDiscount) {
        $formatted .= ' (tối đa ' . number_format($maxDiscount, 0, ',', '.') . 'đ)';
      }
      return $formatted;
    }

    return number_format($value, 0, ',', '.') . 'đ';
  }

  private function formatConstraints(array $template): array
  {
    $constraints = [];

    // Time constraints
    if (!empty($template['valid_days_of_week'])) {
      $days = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
      $validDays = array_map(fn($day) => $days[$day], $template['valid_days_of_week']);
      $constraints['valid_days'] = implode(', ', $validDays);
    }

    if (!empty($template['valid_months'])) {
      $months = [
        1 => 'T1',
        2 => 'T2',
        3 => 'T3',
        4 => 'T4',
        5 => 'T5',
        6 => 'T6',
        7 => 'T7',
        8 => 'T8',
        9 => 'T9',
        10 => 'T10',
        11 => 'T11',
        12 => 'T12'
      ];
      $validMonths = array_map(fn($month) => $months[$month], $template['valid_months']);
      $constraints['valid_months'] = implode(', ', $validMonths);
    }

    // Usage constraints
    if (!empty($template['min_order_value'])) {
      $constraints['min_order'] = 'Đơn tối thiểu ' . number_format($template['min_order_value'], 0, ',', '.') . 'đ';
    }

    if (!empty($template['usage_limit'])) {
      $constraints['usage_limit'] = 'Giới hạn ' . $template['usage_limit'] . ' lần';
    }

    if (!empty($template['per_customer_limit'])) {
      $constraints['per_customer'] = 'Mỗi KH tối đa ' . $template['per_customer_limit'] . ' lần';
    }

    // Membership constraints
    if (!empty($template['applicable_membership_levels'])) {
      $constraints['membership_levels'] = count($template['applicable_membership_levels']) . ' hạng thành viên';
    }

    return $constraints;
  }
}
