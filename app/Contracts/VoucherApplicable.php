<?php

namespace App\Contracts;

use App\Enums\Msg;

interface VoucherApplicable
{
  public function canNotRestoreVoucher(): bool;

  public function getTransactionId(): int;

  public function getSourceIdField(): string;

  public function getMsgVoucherCanNotRestore(): Msg;

  public function getMsgVoucherNotFound(): Msg;

  public function removeVoucherUsed(): void;
}
