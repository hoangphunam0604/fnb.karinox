<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
  /**
   * Kiểm tra quyền của người dùng
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @param  string  $role
   * @return mixed
   */
  public function handle(Request $request, Closure $next, ...$roles)
  {
    if (!Auth::check()) {
      return response()->json(['message' => 'Vui lòng đăng nhập.'], 401);
    }

    /** @var User|null $user */
    $user = Auth::user();
    if (!$user->hasRole($roles)) {
      return response()->json(['message' => 'Bạn không có quyền truy cập.'], 403);
    }

    return $next($request);
  }
}
