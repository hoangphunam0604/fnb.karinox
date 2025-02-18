<?php

namespace App\Services;

class TaxService
{
  protected SystemSettingService $systemSettingService;

  public function __construct(SystemSettingService $systemSettingService)
  {
    $this->systemSettingService = $systemSettingService;
  }

  /**
   * Tính toán thuế từ tổng giá trị đơn hàng
   *
   * @param float $totalPrice Tổng tiền đã bao gồm thuế
   * @param float|null $taxRate Tỷ lệ thuế, nếu null sẽ lấy từ cài đặt hệ thống
   * @return array ['tax_amount' => float, 'total_price_without_vat' => float]
   */
  public function calculateTax(float $totalPrice, ?float $taxRate = null): array
  {
    // Lấy tỷ lệ thuế nếu chưa được truyền vào
    if ($taxRate === null) {
      $taxRate = $this->systemSettingService->getTaxRate();
    }

    // Đảm bảo tỷ lệ thuế hợp lệ
    if ($taxRate <= 0) {
      return ['tax_rate' => null, 'tax_amount' => 0, 'total_price_without_vat' => $totalPrice];
    }

    // Tính tiền trước thuế và tiền thuế
    $totalPriceWithoutVat = $totalPrice / (1 + ($taxRate / 100));
    $taxAmount = $totalPrice - $totalPriceWithoutVat;

    return [
      'tax_rate' => $taxRate,
      'tax_amount' => round($taxAmount, 2),
      'total_price_without_vat' => round($totalPriceWithoutVat, 2)
    ];
  }
}
