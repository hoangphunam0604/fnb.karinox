<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintLabel extends Model
{
  use HasFactory;

  protected $fillable = [
    'invoice_item_id',
    'branch_id',
    'product_code',
    'toppings_text',
    'print_count',
    'last_printed_at',
    'note'
  ];

  protected $casts = [
    'last_printed_at' => 'datetime',
    'print_count' => 'integer'
  ];

  /**
   * Relationships
   */
  public function invoiceItem()
  {
    return $this->belongsTo(InvoiceItem::class);
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

  public function scopeByInvoiceItem($query, $invoiceItemId)
  {
    return $query->where('invoice_item_id', $invoiceItemId);
  }
}
