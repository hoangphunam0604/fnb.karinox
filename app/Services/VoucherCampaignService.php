<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\VoucherCampaign;
use App\Enums\VoucherType;
use App\Enums\DiscountType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherCampaignService extends BaseService
{
  protected function model(): Model
  {
    return new VoucherCampaign();
  }

  /**
   * Create a new voucher campaign
   */
  public function createCampaign(array $data): VoucherCampaign
  {
    return DB::transaction(function () use ($data) {
      $campaign = VoucherCampaign::create($data);

      // Tự động tạo voucher nếu được yêu cầu
      if (isset($data['initial_quantity'])) {
        $this->generateVouchers($campaign, $data['initial_quantity']);
      }

      return $campaign;
    });
  }

  /**
   * Generate vouchers for a campaign
   */
  public function generateVouchers(VoucherCampaign $campaign, int $quantity): array
  {
    if (!$campaign->canGenerateMore()) {
      throw new \InvalidArgumentException('Campaign has reached maximum voucher limit');
    }

    return DB::transaction(function () use ($campaign, $quantity) {
      $maxGenerate = min($quantity, $campaign->getRemainingQuantity());
      $vouchers = [];
      $template = $campaign->voucher_template;

      for ($i = 0; $i < $maxGenerate; $i++) {
        $code = $this->generateUniqueCode($campaign);

        $voucherData = [
          'campaign_id' => $campaign->id,
          'start_date' =>  $campaign->start_date,
          'end_date' => $campaign->end_date,
          'code' => $code,
          'description' => $template['description'] ?? $campaign->name,
          'voucher_type' => $template['voucher_type'] ?? VoucherType::STANDARD->value,
          'discount_type' => $template['discount_type'],
          'discount_value' => $template['discount_value'],
          'max_discount' => $template['discount_type'] == 'percentage' ? $template['max_discount'] ?? null : null,
          'min_order_value' => $template['min_order_value'] ?? null,
          'usage_limit' => $template['usage_limit'] ?? 1, // Default: single use
          'per_customer_limit' => $template['per_customer_limit'] ?? null,
          'per_customer_daily_limit' => $template['per_customer_daily_limit'] ?? null,
          'is_active' => $template['is_active'] ?? true,
          'disable_holiday' => $template['disable_holiday'] ?? false,
          'applicable_membership_levels' => $template['applicable_membership_levels'] ?? null,
          'valid_days_of_week' => $template['valid_days_of_week'] ?? null,
          'valid_weeks_of_month' => $template['valid_weeks_of_month'] ?? null,
          'valid_months' => $template['valid_months'] ?? null,
          'valid_time_ranges' => $template['valid_time_ranges'] ?? null,
          'excluded_dates' => $template['excluded_dates'] ?? null,
        ];

        $voucher = Voucher::create($voucherData);

        // Sync branches if specified in template
        if (isset($template['branch_ids']) && is_array($template['branch_ids'])) {
          $voucher->branches()->sync($template['branch_ids']);
        }

        $vouchers[] = $voucher;
      }

      $campaign->incrementGenerated($maxGenerate);

      return $vouchers;
    });
  }

  /**
   * Generate unique voucher code
   */
  private function generateUniqueCode(VoucherCampaign $campaign): string
  {
    $maxAttempts = 100;
    $attempts = 0;

    do {
      $random = $this->generateRandomString($campaign->code_length);
      $code = str_replace(
        ['{PREFIX}', '{RANDOM_' . $campaign->code_length . '}', '{RANDOM}'],
        [$campaign->code_prefix, $random, $random],
        $campaign->code_format
      );

      $attempts++;

      if ($attempts >= $maxAttempts) {
        throw new \RuntimeException('Unable to generate unique voucher code after ' . $maxAttempts . ' attempts');
      }
      // If format included an underscore between prefix and random token, collapse it
      // e.g. turn PREFIX_ABC123 into PREFIXABC123
      $code = str_replace($campaign->code_prefix . '_' . $random, $campaign->code_prefix . $random, $code);
    } while (Voucher::where('code', $code)->exists());

    return $code;
  }

  /**
   * Generate random string for voucher codes
   */
  private function generateRandomString(int $length): string
  {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    return substr(str_shuffle(str_repeat($characters, ceil($length / strlen($characters)))), 0, $length);
  }

  /**
   * Get campaign analytics
   */
  public function getCampaignAnalytics(VoucherCampaign $campaign): array
  {
    $vouchers = $campaign->vouchers();

    return [
      'campaign_info' => [
        'id' => $campaign->id,
        'name' => $campaign->name,
        'type' => $campaign->campaign_type,
        'is_active' => $campaign->isActive(),
        'days_remaining' => $campaign->getDaysRemaining(),
      ],
      'generation_stats' => [
        'target_quantity' => $campaign->target_quantity,
        'generated_quantity' => $campaign->generated_quantity,
        'remaining_quantity' => $campaign->getRemainingQuantity(),
        'generation_rate' => $campaign->getGenerationRate(),
      ],
      'usage_stats' => [
        'used_quantity' => $campaign->used_quantity,
        'unused_quantity' => $campaign->generated_quantity - $campaign->used_quantity,
        'usage_rate' => $campaign->getUsageRate(),
      ],
      'daily_usage' => $this->getDailyUsageStats($campaign),
      'top_branches' => $this->getTopBranchesStats($campaign),
    ];
  }

  /**
   * Get daily usage statistics for last 30 days
   */
  private function getDailyUsageStats(VoucherCampaign $campaign): array
  {
    return DB::table('voucher_usages')
      ->join('vouchers', 'voucher_usages.voucher_id', '=', 'vouchers.id')
      ->where('vouchers.campaign_id', $campaign->id)
      ->where('voucher_usages.used_at', '>=', Carbon::now()->subDays(30))
      ->selectRaw('DATE(voucher_usages.used_at) as date, COUNT(*) as count')
      ->groupBy('date')
      ->orderBy('date')
      ->get()
      ->toArray();
  }

  /**
   * Get top branches by voucher usage
   */
  private function getTopBranchesStats(VoucherCampaign $campaign, int $limit = 5): array
  {
    return DB::table('voucher_usages')
      ->join('vouchers', 'voucher_usages.voucher_id', '=', 'vouchers.id')
      ->join('voucher_branches', 'vouchers.id', '=', 'voucher_branches.voucher_id')
      ->join('branches', 'voucher_branches.branch_id', '=', 'branches.id')
      ->where('vouchers.campaign_id', $campaign->id)
      ->selectRaw('branches.name, COUNT(*) as usage_count')
      ->groupBy('branches.id', 'branches.name')
      ->orderByDesc('usage_count')
      ->limit($limit)
      ->get()
      ->toArray();
  }

  /**
   * Bulk deactivate vouchers in campaign
   */
  public function deactivateCampaignVouchers(VoucherCampaign $campaign): int
  {
    return $campaign->vouchers()->update(['is_active' => false]);
  }

  /**
   * Bulk activate vouchers in campaign
   */
  public function activateCampaignVouchers(VoucherCampaign $campaign): int
  {
    return $campaign->vouchers()->update(['is_active' => true]);
  }

  /**
   * Export campaign voucher codes
   */
  public function exportVoucherCodes(VoucherCampaign $campaign, bool $unusedOnly = false): array
  {
    $query = $campaign->vouchers();

    if ($unusedOnly) {
      $query->where('applied_count', 0);
    }

    return $query->pluck('code')->toArray();
  }
}
