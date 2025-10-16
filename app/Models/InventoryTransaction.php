<?php

namespace App\Models;

use App\Enums\InventoryTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class InventoryTransaction extends Model
{
  use HasFactory;

  protected $fillable = [
    'user_id',
    'reference_id',
    'transaction_type',
    'branch_id',
    'destination_branch_id',
    'note'
  ];

  protected $casts = [
    'transaction_type' => InventoryTransactionType::class,
  ];

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($transaction) {
      $transaction->user_id = Auth::id();
    });
  }

  /**
   * Định nghĩa quan hệ với chi nhánh thực hiện giao dịch.
   */
  public function branch()
  {
    return $this->belongsTo(Branch::class);
  }

  /**
   * Định nghĩa quan hệ với chi nhánh đích (nếu là giao dịch chuyển kho).
   */
  public function destinationBranch()
  {
    return $this->belongsTo(Branch::class, 'destination_branch_id');
  }

  /**
   * Định nghĩa quan hệ với người thực hiện giao dịch.
   */
  public function user()
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Định nghĩa quan hệ với các item trong giao dịch.
   */
  public function items()
  {
    return $this->hasMany(InventoryTransactionItem::class, 'inventory_transaction_id');
  }

  /**
   * Kiểm tra loại giao dịch bằng Enum.
   */
  public function isImport(): bool
  {
    return $this->transaction_type->isImport();
  }

  public function isExport(): bool
  {
    return $this->transaction_type->isExport();
  }

  public function isSale(): bool
  {
    return $this->transaction_type->isSale();
  }

  public function isReturn(): bool
  {
    return $this->transaction_type->isReturn();
  }

  public function isTransferOut(): bool
  {
    return $this->transaction_type->isTransferOut();
  }

  public function isTransferIn(): bool
  {
    return $this->transaction_type->isTransferIn();
  }

  public function isStocktaking(): bool
  {
    return $this->transaction_type->isStocktaking();
  }
}
