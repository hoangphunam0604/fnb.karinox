<?php

use App\Enums\UserRole;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('order.{orderId}', function ($user, $orderId) {
  return $user->hasAnyRole([
    UserRole::ADMIN->value,
    UserRole::MANAGER->value,
    UserRole::CASHIER->value,
  ]);
});

Broadcast::channel('kitchen.branch.{branchId}', function ($user, $branchId) {
  return true;
});
