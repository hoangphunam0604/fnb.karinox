<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\VoucherType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherCampaign extends Model
{
  use HasFactory;

  protected $fillable = [
    'name',
    'description',
    'campaign_type',
    'start_date',
    'end_date',
    'target_quantity',
    'generated_quantity',
    'used_quantity',
    'voucher_template',
    'code_prefix',
    'code_format',
    'code_length',
    'is_active',
    'auto_generate',
    'created_by',
  ];

  protected $casts = [
    'voucher_template' => 'array',
    'start_date' => 'datetime',
    'end_date' => 'datetime',
    'target_quantity' => 'integer',
    'generated_quantity' => 'integer',
    'used_quantity' => 'integer',
    'code_length' => 'integer',
    'is_active' => 'boolean',
    'auto_generate' => 'boolean',
  ];

  // Relationships
  public function vouchers(): HasMany
  {
    return $this->hasMany(Voucher::class, 'campaign_id');
  }

  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  // Scopes
  public function scopeActive($query)
  {
    return $query->where('is_active', true)
      ->where('start_date', '<=', now())
      ->where('end_date', '>=', now());
  }

  public function scopeByType($query, string $type)
  {
    return $query->where('campaign_type', $type);
  }

  // Helper methods
  public function isActive(): bool
  {
    return $this->is_active &&
      $this->start_date <= now() &&
      $this->end_date >= now();
  }

  public function canGenerateMore(): bool
  {
    return $this->generated_quantity < $this->target_quantity;
  }

  public function getRemainingQuantity(): int
  {
    return max(0, $this->target_quantity - $this->generated_quantity);
  }

  public function getUsageRate(): float
  {
    if ($this->generated_quantity === 0) {
      return 0;
    }

    return round(($this->used_quantity / $this->generated_quantity) * 100, 2);
  }

  public function getGenerationRate(): float
  {
    if ($this->target_quantity === 0) {
      return 0;
    }

    return round(($this->generated_quantity / $this->target_quantity) * 100, 2);
  }

  public function getDaysRemaining(): int
  {
    return max(0, now()->diffInDays($this->end_date, false));
  }

  public function incrementUsed(int $count = 1): void
  {
    $this->increment('used_quantity', $count);
  }

  public function incrementGenerated(int $count = 1): void
  {
    $this->increment('generated_quantity', $count);
  }
}
