<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintQueue extends Model
{
  use HasFactory;

  protected $table = 'print_queue';
  protected $fillable = [
    'branch_id',
    'type',
    'content',
    'metadata',
    'status',
    'device_id',
    'priority',
    'processed_at',
    'error_message',
    'retry_count'
  ];

  protected $casts = [
    'metadata' => 'array',
    'processed_at' => 'datetime',
    'created_at' => 'datetime',
    'updated_at' => 'datetime'
  ];

  /**
   * Relationship with Branch
   */
  public function branch()
  {
    return $this->belongsTo(Branch::class);
  }

  /**
   * Relationship with Order (from metadata)
   */
  public function order()
  {
    if (isset($this->metadata['order_id'])) {
      return Order::find($this->metadata['order_id']);
    }
    return null;
  }

  /**
   * Scopes
   */
  public function scopePending($query)
  {
    return $query->where('status', 'pending');
  }

  public function scopeForDevice($query, $deviceId)
  {
    return $query->where(function ($q) use ($deviceId) {
      $q->where('device_id', $deviceId)
        ->orWhereNull('device_id');
    });
  }

  public function scopeByPriority($query)
  {
    return $query->orderByRaw("CASE 
            WHEN priority = 'high' THEN 1 
            WHEN priority = 'normal' THEN 2 
            WHEN priority = 'low' THEN 3 
            ELSE 4 END")
      ->orderBy('created_at');
  }

  /**
   * Mark as processed
   */
  public function markAsProcessed()
  {
    $this->update([
      'status' => 'processed',
      'processed_at' => now()
    ]);
  }

  /**
   * Mark as failed
   */
  public function markAsFailed(string $errorMessage)
  {
    $this->update([
      'status' => 'failed',
      'error_message' => $errorMessage,
      'retry_count' => $this->retry_count + 1
    ]);
  }

  /**
   * Check if can retry
   */
  public function canRetry(int $maxRetries = 3)
  {
    return $this->retry_count < $maxRetries;
  }

  /**
   * Retry the job
   */
  public function retry()
  {
    if ($this->canRetry()) {
      $this->update([
        'status' => 'pending',
        'error_message' => null
      ]);
      return true;
    }
    return false;
  }
}
