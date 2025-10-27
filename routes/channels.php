<?php

use App\Enums\UserRole;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('private-order.{orderId}', function ($user, $orderId) {
  // Handle null user (unauthenticated)
  if (!$user) {
    return false;
  }

  return $user->hasAnyRole([
    UserRole::ADMIN->value,
    UserRole::MANAGER->value,
    UserRole::CASHIER->value,
  ]);
});

Broadcast::channel('kitchen.branch.{branchId}', function ($user, $branchId) {
  // Handle null user (unauthenticated)
  if (!$user) {
    return false;
  }

  if ($user->hasRole(UserRole::KITCHEN_STAFF->value))
    return true;

  if ($user->hasAnyRole([
    UserRole::MANAGER->value,
    UserRole::KITCHEN_STAFF->value,
  ]))
    return $user->managesBranch($branchId);

  return true;
});
