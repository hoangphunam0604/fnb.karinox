<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceVoucher extends Model
{
  use HasFactory;
  public $incrementing = false; // Không sử dụng id tự tăng
  protected $primaryKey = ['invoice_id', 'voucher_id']; // Định nghĩa khóa chính là 2 cột
  protected $fillable = ['invoice_id', 'voucher_id', 'invoice_total_before_discount', 'discount_amount'];


  public function invoice()
  {
    return $this->belongsTo(Invoice::class);
  }

  public function voucher()
  {
    return $this->belongsTo(Voucher::class);
  }
}
