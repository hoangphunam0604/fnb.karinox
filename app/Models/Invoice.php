<?php

namespace App\Models;

use App\Events\InvoiceCompleted;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;

use App\Contracts\PointEarningTransaction;
use App\Contracts\RewardPointUsable;
use App\Contracts\VoucherApplicable;
use App\Enums\Msg;
use App\Enums\PointHistoryNote;

class Invoice extends Model implements PointEarningTransaction, RewardPointUsable, VoucherApplicable
{
  use HasFactory;
  protected $fillable = [
    'branch_id',
    'order_id',

    'subtotal_price',
    'discount_amount',
    'reward_discount',
    'total_price',

    'paid_amount',
    'change_amount',

    'tax_rate',
    'tax_amount',
    'total_price_without_vat',

    'reward_points_used',
    'earned_loyalty_points',
    'earned_reward_points',

    'voucher_id',
    'sales_channel',
    'invoice_status',
    'payment_status',
    'payment_method',
    'note',
    'customer_id',
    'loyalty_card_number',
    'customer_name',
    'customer_phone',
    'customer_email',
    'customer_address',
  ];


  protected $casts = [
    'invoice_status' => InvoiceStatus::class,
    'payment_status' => PaymentStatus::class,
  ];

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($invoice) {
      if (!$invoice->code)
        $invoice->code = self::generateInvoiceCode($invoice->branch_id);
    });
    static::updated(function ($invoice) {
      if ($invoice->status === 'completed' && $invoice->getOriginal('status') !== 'completed') {
        event(new InvoiceCompleted($invoice));
      }
    });
  }

  public static function generateInvoiceCode($branchId)
  {
    $latestOrder = self::whereDate('created_at', now()->toDateString())
      ->where('branch_id', $branchId)
      ->orderBy('id', 'desc')
      ->first();

    $increment = $latestOrder ? ((int) substr($latestOrder->order_code, -4)) + 1 : 1;

    return sprintf("HD-%02d-%s-%04d", $branchId, now()->format('ymd'), $increment);
  }
  public function items()
  {
    return $this->hasMany(InvoiceItem::class);
  }

  public function order()
  {
    return $this->belongsTo(Order::class);
  }

  public function voucher()
  {
    return $this->belongsTo(Voucher::class)->withDefault();
  }

  /**
   * Mối quan hệ với khách hàng
   */
  public function customer()
  {
    return $this->belongsTo(Customer::class);
  }

  /**
   * Kiểm tra hóa đơn đã thanh toán đầy đủ chưa
   */
  public function isPaid()
  {
    return $this->payment_status === PaymentStatus::PAID;
  }

  /**
   * Kiểm tra hóa đơn đã hoàn tất chưa
   */
  public function isCompleted()
  {
    return $this->invoice_status === InvoiceStatus::COMPLETED;
  }

  /**
   * Đánh dấu hóa đơn là hoàn tất nếu đã thanh toán đầy đủ
   */
  public function markAsCompleted()
  {
    if ($this->isPaid()) {
      $this->invoice_status = InvoiceStatus::COMPLETED;
      $this->save();
    }
  }

  public function getTransactionType(): string
  {
    return 'invoice';
  }

  public function getTransactionId(): int
  {
    return $this->id;
  }

  public function getCustomer(): ?Customer
  {
    return $this->customer;
  }

  public function getTotalAmount(): float
  {
    return $this->total_price;
  }

  public function canEarnPoints(): bool
  {
    return $this->me && $this->total_price > 0;
  }

  public function getEarnedLoyaltyPoints(): float
  {
    return $this->earned_loyalty_points;
  }

  public function getEarnedRewardPoints(): float
  {
    return $this->earned_reward_points;
  }

  public function getEarnedPointsNote(): string
  {
    return "Sử dụng điểm thanh toán";
  }

  public function getRestoredPointsNote(): string
  {
    return "Thu hồi điểm từ hoá đơn đã huỷ";
  }


  public function updatePoints($loyaltyPoints, $rewardPoints): void
  {
    $this->earned_loyalty_points = $loyaltyPoints;
    $this->earned_reward_points = $rewardPoints;
    $this->save();
  }

  public function removePoints(): void
  {
    $this->updatePoints(0, 0);
  }

  public function applyRewardPointsDiscount(int $usedRewardPoints, int $rewardDiscount): void
  {
    $this->update([
      'reward_points_used' => $usedRewardPoints,
      'reward_discount' => $rewardDiscount
    ]);
  }

  public function getRewardPointsUsed(): float
  {
    return $this->reward_points_used;
  }

  public function getNoteToUseRewardPoints(): PointHistoryNote
  {
    return PointHistoryNote::INVOICE_USER_REWARD_POINTS;
  }

  public function getNoteToRestoreRewardPoints(): PointHistoryNote
  {
    return PointHistoryNote::INVOICE_RESTORE_REWARD_POINTS;
  }

  public function remoreRewardPointsUsed(): void
  {
    $this->reward_points_used = 0;
    $this->save();
  }


  public function getSourceIdField(): string
  {
    return 'invoice_id';
  }

  public function canNotRestoreVoucher(): bool
  {
    return $this->order_status === 'completed' || !$this->voucher_id;;
  }

  public function getMsgVoucherCanNotRestore(): Msg
  {
    return Msg::VOUCHER_CANNOT_RESTORE_FROM_INVOICE;
  }

  public function getMsgVoucherNotFound(): Msg
  {
    return Msg::VOUCHER_RESTORE_NOT_FOUND;
  }

  public function removeVoucherUsed(): void
  {
    $this->update(['voucher_id' => null]);
  }
}
