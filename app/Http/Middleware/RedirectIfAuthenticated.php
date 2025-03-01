<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
  public function handle($request, Closure $next)
  {
    if (Auth::check()) {
      /** @var User|null $user */
      $user = Auth::user();

      if (!$user->currentBranch) {
        return redirect()->route('branches.index');
      }

      switch ($user->getRoleNames()->first()) {
        case UserRole::ADMIN:
        case UserRole::MANAGER:
          return redirect()->route('admin.dashboard');
        case UserRole::KITCHEN_STAFF:
          return redirect()->route('kitchen.orders');
        case UserRole::CASHIER:
          return redirect()->route('pos.tables');
        default:
          return redirect()->route('forbidden');
      }
    }

    return $next($request);
  }
}
