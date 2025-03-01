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
      $auth_redirect = $user->login_redirect;

      return redirect()->to($auth_redirect);
    }

    return $next($request);
  }
}
