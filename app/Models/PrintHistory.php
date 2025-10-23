<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintHistory extends Model
{
  use HasFactory;

  protected $table = 'print_histories';

  protected $fillable = [
    'print_id',
    'branch_id',
    'type',
    'metadata',
    'status',
    'requested_at',
    'printed_at',
    'confirmed_at'
  ];

  protected $casts = [
    'metadata' => 'array',
    'requested_at' => 'datetime',
    'printed_at' => 'datetime',
    'confirmed_at' => 'datetime'
  ];

  /**
   * Relationship với Branch
   */
  public function branch()
  {
    return $this->belongsTo(Branch::class);
  }

  /**
   * Lấy Order từ metadata
   */
  public function order()
  {
    if (isset($this->metadata['order_id'])) {
      return Order::find($this->metadata['order_id']);
    }
    return null;
  }

  /**
   * Mark as printed (frontend đã in xong)
   */
  public function markAsPrinted()
  {
    $this->update([
      'status' => 'printed',
      'printed_at' => now()
    ]);
  }

  /**
   * Mark as confirmed (đã xác nhận hoàn thành)
   */
  public function markAsConfirmed()
  {
    $this->update([
      'status' => 'confirmed',
      'confirmed_at' => now()
    ]);
  }

  /**
   * Mark as failed
   */
  public function markAsFailed()
  {
    $this->update([
      'status' => 'failed'
    ]);
  }

  /**
   * Scopes
   */
  public function scopeByBranch($query, $branchId)
  {
    return $query->where('branch_id', $branchId);
  }

  public function scopeByStatus($query, $status)
  {
    return $query->where('status', $status);
  }

  public function scopeByType($query, $type)
  {
    return $query->where('type', $type);
  }

  public function scopeToday($query)
  {
    return $query->whereDate('requested_at', today());
  }
}
