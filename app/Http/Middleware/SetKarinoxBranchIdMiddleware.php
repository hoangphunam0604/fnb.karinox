<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetKarinoxBranchIdMiddleware
{
  public function handle(Request $request, Closure $next)
  {
    if ($request->hasHeader('Karinox-Branch-Id')) {
      /**
       * Lưu branchId vào request để dùng ở bất cứ đâu trong ứng dụng 
       * Sử dụng:$branchId = app('karinox_branch_id');
       **/
      app()->instance('karinox_branch_id', $request->header('Karinox-Branch-Id'));
    }

    return $next($request);
  }
}
