<?php

namespace App\Services;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class VoucherService
{
  /**
   * Tạo hoặc cập nhật voucher
   */
  public function saveVoucher(array $data, $voucherId = null)
  {
    return DB::transaction(function () use ($data, $voucherId) {
      $voucher = $voucherId
        ? Voucher::findOrFail($voucherId)
        : new Voucher();

      $voucher->fill([
        'code' => strtoupper($data['code'] ?? $voucher->code),
        'discount_type' => $data['discount_type'] ?? $voucher->discount_type,
        'discount_value' => $data['discount_value'] ?? $voucher->discount_value,
        'min_order_value' => $data['min_order_value'] ?? $voucher->min_order_value,
        'max_discount' => $data['max_discount'] ?? $voucher->max_discount,
        'usage_limit' => $data['usage_limit'] ?? $voucher->usage_limit,
        'used_count' => $voucherId ? $voucher->used_count : 0, // Không reset lượt dùng khi cập nhật
        'expires_at' => $data['expires_at'] ?? $voucher->expires_at,
        'status' => $data['status'] ?? $voucher->status,
      ]);

      $voucher->save();
      return $voucher;
    });
  }

  /**
   * Xóa voucher
   */
  public function deleteVoucher($voucherId)
  {
    return Voucher::findOrFail($voucherId)->delete();
  }

  /**
   * Tìm kiếm voucher theo mã
   */
  public function findVoucher($code)
  {
    return Voucher::findByCode($code);
  }

  /**
   * Lấy danh sách vouchers (phân trang)
   */
  public function getVouchers($perPage = 10)
  {
    return Voucher::orderBy('created_at', 'desc')->paginate($perPage);
  }

  /**
   * Kiểm tra xem voucher có hợp lệ không
   */
  public function isValidVoucher($code)
  {
    $voucher = $this->findVoucher($code);
    return $voucher ? $voucher->isValid() : false;
  }

  /**
   * Sử dụng voucher: kiểm tra hợp lệ và tăng số lần sử dụng
   */
  public function applyVoucher($code)
  {
    return DB::transaction(function () use ($code) {
      $voucher = $this->findVoucher($code);

      if (!$voucher || !$voucher->isValid()) {
        throw new ModelNotFoundException("Voucher không hợp lệ hoặc đã hết hạn.");
      }

      $voucher->incrementUsage();
      return $voucher;
    });
  }
}
