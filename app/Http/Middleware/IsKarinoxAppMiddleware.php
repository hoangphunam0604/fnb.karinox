<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsKarinoxAppMiddleware
{
  public function handle(Request $request, Closure $next)
  {
    if ($request->hasHeader('karinox-app-id') && in_array($request->header('karinox-app-id'), ["karinox-app-pos", "karinox-app-kitchen", "karinox-app-admin"])) {
      return $next($request);
    }
    abort(403, 'Bạn không có quyền truy cập.');
  }
}
