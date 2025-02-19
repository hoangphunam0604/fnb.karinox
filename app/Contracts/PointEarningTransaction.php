<?php

namespace App\Contracts;

use App\Models\Customer;

interface PointEarningTransaction
{
  public function getTransactionId(): int;

  public function getTransactionType(): string;

  public function getCustomer(): ?Customer;

  public function getTotalAmount(): float;

  public function canEarnPoints(): bool;

  public function updatePoints($loyaltyPoints, $rewardPoints): void;

  public function getEarnedLoyaltyPoints(): float;

  public function getEarnedRewardPoints(): float;

  public function getEarnedPointsNote(): string;

  public function getRestoredPointsNote(): string;

  public function restorePoints(): void;
}
