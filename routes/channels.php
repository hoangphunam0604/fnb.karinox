<?php

use App\Enums\UserRole;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/


Broadcast::channel('kitchen-orders.ready.{branchId}', function ($user, $branchId) {
  return $user->hasRole(UserRole::WAITER) && $user->branch_id == $branchId;
});
