<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPoint extends Model
{
  use HasFactory;

  protected $fillable = [
    'customer_id',
    'invoice_id',
    'points',
    'type',
    'description',
    'expired_at',
  ];

  /**
   * Liên kết với khách hàng
   */
  public function customer()
  {
    return $this->belongsTo(Customer::class);
  }

  /**
   * Liên kết với hóa đơn (invoice)
   */
  public function invoice()
  {
    return $this->belongsTo(Invoice::class);
  }
}
