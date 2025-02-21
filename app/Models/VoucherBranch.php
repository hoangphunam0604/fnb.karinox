<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherBranch extends Model
{
  use HasFactory;

  public $incrementing = false; // Không sử dụng id tự tăng
  protected $primaryKey = ['voucher_id', 'branch_id']; // Định nghĩa khóa chính là 2 cột
  public $timestamps = false; // Giữ timestamps nếu cần theo dõi thời gian cập nhật

  protected $fillable = ['voucher_id', 'branch_id'];

  //Ghi đè phương thức delete để xử lý xóa dữ liệu 
  public function delete()
  {
    return static::where('voucher_id', $this->voucher_id)
      ->where('branch_id', $this->branch_id)
      ->delete();
  }

  public function voucher()
  {
    return $this->belongsTo(Voucher::class);
  }

  public function branch()
  {
    return $this->belongsTo(Branch::class);
  }
}
