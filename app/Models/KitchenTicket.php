<?php

namespace App\Models;

use App\Enums\KitchenTicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitchenTicket extends Model
{
  use HasFactory;

  protected $fillable = [
    'order_id',
    'branch_id',
    'table_id',
    'status',
    'priority',
    'note',
    'accepted_by',
    'created_by',
    'updated_by'
  ];
  protected $casts = [
    'status' => KitchenTicketStatus::class,
  ];

  public function items(): HasMany
  {
    return $this->hasMany(KitchenTicketItem::class);
  }

  public function order(): BelongsTo
  {
    return $this->belongsTo(Order::class);
  }

  public function table(): BelongsTo
  {
    return $this->belongsTo(TableAndRoom::class);
  }
  public function branch(): BelongsTo
  {
    return $this->belongsTo(Branch::class);
  }
  /**
   * Quan hệ với User (người nhận món)
   */
  public function acceptedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'accepted_by')->withDefault(['fullname' => 'Chưa có người nhận']);
  }

  /**
   * Quan hệ với User (người tạo)
   */
  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  /**
   * Quan hệ với User (người cập nhật)
   */
  public function updatedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'updated_by')->withDefault();
  }
}
