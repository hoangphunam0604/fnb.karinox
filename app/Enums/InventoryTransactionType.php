<?php

namespace App\Enums;

use Illuminate\Support\Arr;

enum InventoryTransactionType: string
{
  case IMPORT = 'import';
  case EXPORT = 'export';
  case SALE = 'sale';
  case RETURN = 'return';
  case TRANSFER_OUT = 'transfer_out';
  case TRANSFER_IN = 'transfer_in';
  case STOCKTAKING = 'stocktaking';

  public function getLabel(): string
  {
    return match ($this) {
      self::IMPORT => "Nhập kho (khi mua hàng hoặc bổ sung hàng tồn kho)",
      self::EXPORT => "Xuất kho (chuyển hàng giữa các chi nhánh hoặc loại bỏ hàng hỏng)",
      self::SALE => "Bán hàng (giảm tồn kho khi đơn hàng hoàn tất)",
      self::RETURN => "Trả hàng (khách trả lại hàng, tăng tồn kho)",
      self::TRANSFER_OUT => "Xuất kho để chuyển đến chi nhánh khác.",
      self::TRANSFER_IN => "Nhập kho từ một chi nhánh khác.",
      self::STOCKTAKING => "Điều chỉnh tồn kho dựa trên kết quả kiểm kho.",
    };
  }

  /**
   * Kiểm tra xem có phải là giao dịch nhập kho không.
   */
  public function isImport(): bool
  {
    return $this === self::IMPORT;
  }

  /**
   * Kiểm tra xem có phải là giao dịch xuất kho không.
   */
  public function isExport(): bool
  {
    return $this === self::EXPORT;
  }

  /**
   * Kiểm tra xem có phải là giao dịch bán hàng không.
   */
  public function isSale(): bool
  {
    return $this === self::SALE;
  }

  /**
   * Kiểm tra xem có phải là giao dịch hoàn trả không.
   */
  public function isReturn(): bool
  {
    return $this === self::RETURN;
  }

  /**
   * Kiểm tra xem có phải là giao dịch chuyển kho đi không.
   */
  public function isTransferOut(): bool
  {
    return $this === self::TRANSFER_OUT;
  }

  /**
   * Kiểm tra xem có phải là giao dịch nhận hàng chuyển kho không.
   */
  public function isTransferIn(): bool
  {
    return $this === self::TRANSFER_IN;
  }

  /**
   * Kiểm tra xem có phải là giao dịch kiểm kê kho không.
   */
  public function isStocktaking(): bool
  {
    return $this === self::STOCKTAKING;
  }

  // Kiểm tra trạng thái hợp lệ
  public static function isValid(string $status): bool
  {
    return in_array($status, self::casesAsArray());
  }

  public static function casesAsArray(): array
  {
    return array_column(self::cases(), 'value');
  }

  public static function fake(): self
  {
    return Arr::random(self::cases());
  }
}
