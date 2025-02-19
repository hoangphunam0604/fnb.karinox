<?php

namespace App\Contracts;

use App\Enums\PointHistoryNote;
use App\Models\Customer;

interface RewardPointUsable
{
  public function getTransactionId(): int;

  public function getTransactionType(): string;

  public function getCustomer(): ?Customer;

  public function getTotalAmount(): float;

  public function getRewardPointUsed(): float;

  public function getNoteToUseRewardPoints(): PointHistoryNote;

  public function getNoteToRestoreRewardPoints(): PointHistoryNote;

  public function applyRewardPointsDiscount(int $usedRewardPoints, int  $rewardDiscount): void;

  public function remoreRewardPointsUsed(): void;
}
