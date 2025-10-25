<?php

namespace App\Models;

use App\Contracts\RewardPointUsable;
use App\Contracts\VoucherApplicable;
use App\Enums\Msg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PointHistoryNote;

class Order extends Model implements RewardPointUsable, VoucherApplicable
{
  use HasFactory;

  protected $fillable = [
    'extend_id', // Id đơn hàng kế thừa, dùng cho các chi nhánh khác nhau sử dụng chung mã giảm giá
    'code',
    'order_status',
    'ordered_at',
    'user_id',
    'receiver_id',
    'customer_id',
    'branch_id',
    'table_id',
    'invoice_id',

    'subtotal_price',
    'discount_amount',
    'reward_points_used',
    'reward_discount',
    'total_price',

    'voucher_id',
    'voucher_code',

    'payment_status',
    'payment_method',
    'payment_url',
    'payment_started_at',
    'paid_at',

    'note',
    'printed_bill',
    'printed_bill_at',
  ];

  protected $casts = [
    'subtotal_price'  => 'integer',
    'discount_amount' => 'integer',
    'reward_points_used'  => 'integer',
    'reward_discount' => 'integer',
    'total_price' => 'integer',
    'order_status' => OrderStatus::class,
    'printed_bill'  =>  'boolean',
    'payment_status' => PaymentStatus::class,
    'payment_started_at' =>  'datetime',
    'paid_at' =>  'datetime',
    'printed_bill_at' =>  'datetime',
  ];

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($order) {
      if (!$order->code)
        $order->code = self::generateOrderCode($order->branch_id);
    });

    static::updating(function ($order) {
      if ($order->isDirty('order_status')) {
        OrderHistory::create([
          'order_id'   => $order->id,
          'old_status' => $order->getOriginal('order_status'),
          'new_status' => $order->order_status,
          'user_id'    => Auth::id(),
          'note'      => 'Cập nhật trạng thái đơn hàng.'
        ]);
      }
    });
  }
  public static function generateOrderCode($branchId)
  {
    $latestOrder = self::whereDate('created_at', now()->toDateString())
      ->where('branch_id', $branchId)
      ->orderBy('id', 'desc')
      ->first();

    $increment = $latestOrder ? ((int) substr($latestOrder->code, -4)) + 1 : 1;

    return sprintf("CN%02dN%sORD%04d", $branchId, now()->format('ymd'), $increment);
  }

  public function receiver()
  {
    return $this->belongsTo(User::class, 'receiver_id')->withDefault();
  }

  public function staff()
  {
    return $this->belongsTo(User::class, 'user_id')->withDefault();
  }

  public function customer()
  {
    return $this->belongsTo(Customer::class);
  }

  public function table()
  {
    return $this->belongsTo(TableAndRoom::class, 'table_id')->withDefault();
  }

  public function branch()
  {
    return $this->belongsTo(Branch::class)->withDefault();
  }

  public function histories()
  {
    return $this->hasMany(OrderHistory::class);
  }

  public function items()
  {
    return $this->hasMany(OrderItem::class);
  }

  public function voucher()
  {
    return $this->belongsTo(Voucher::class)->withDefault([]);
  }

  public function invoice()
  {
    return $this->belongsTo(Invoice::class);
  }
  /**
   * Kiểm tra đơn hàng đã hoàn tất chưa
   */
  public function isCompleted()
  {
    return $this->order_status === OrderStatus::COMPLETED;
  }


  public function getTransactionType(): string
  {
    return 'order';
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


  public function getRewardPointsUsed(): float
  {
    return $this->reward_points_used;
  }

  public function getNoteToUseRewardPoints(): PointHistoryNote
  {
    return PointHistoryNote::ORDER_USER_REWARD_POINTS;
  }

  public function getNoteToRestoreRewardPoints(): PointHistoryNote
  {
    return PointHistoryNote::ORDER_RESTORE_REWARD_POINTS;
  }

  public function remoreRewardPointsUsed(): void
  {
    $this->total_price = $this->total_price + $this->reward_discount;
    $this->reward_points_used = 0;
    $this->reward_discount = 0;
    $this->save();
  }

  public function applyRewardPointsDiscount(int $usedRewardPoints, int $rewardDiscount): void
  {
    $this->update([
      'reward_points_used' => $usedRewardPoints,
      'reward_discount' => $rewardDiscount
    ]);
  }

  public function getSourceIdField(): string
  {
    return 'order_id';
  }

  public function canNotRestoreVoucher(): bool
  {
    return $this->order_status === OrderStatus::COMPLETED || !$this->voucher_id;
  }

  public function getMsgVoucherCanNotRestore(): Msg
  {
    return Msg::VOUCHER_CANNOT_RESTORE_FROM_ORDER;
  }

  public function getMsgVoucherNotFound(): Msg
  {
    return Msg::VOUCHER_RESTORE_NOT_FOUND;
  }

  public function removeVoucherUsed(): void
  {
    $total_price = $this->total_price + $this->discount_amount;
    $this->discount_amount = 0;
    $this->total_price = $total_price;
    $this->voucher_code = null;
    $this->voucher_id = null;
    $this->save();
  }
}
