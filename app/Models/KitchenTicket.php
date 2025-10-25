<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitchenTicket extends Model
{
  use HasFactory;

  protected $fillable = [
    'branch_id',
    'invoice_id',
    'metadata',
    'print_count',
    'last_printed_at',
    'note'
  ];
  protected $casts = [
    'metadata' => 'array',
    'last_printed_at' => 'datetime',
    'print_count' => 'integer'
  ];

  /**
   * Relationships
   */
  public function invoice()
  {
    return $this->belongsTo(Invoice::class);
  }

  public function branch()
  {
    return $this->belongsTo(Branch::class);
  }

  /**
   * Methods
   */
  public function markAsPrinted()
  {
    $this->increment('print_count');
    $this->update(['last_printed_at' => now()]);
  }

  /**
   * Scopes
   */
  public function scopeByBranch($query, $branchId)
  {
    return $query->where('branch_id', $branchId);
  }

  public function scopeByInvoice($query, $invoiceId)
  {
    return $query->where('invoice_id', $invoiceId);
  }
}
