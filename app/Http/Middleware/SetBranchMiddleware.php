<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetBranchMiddleware
{
  public function handle(Request $request, Closure $next)
  {
    if ($request->hasHeader('karinox-branch-id')) {
      /**
       * Lưu branchId vào request để dùng ở bất cứ đâu trong ứng dụng 
       * Sử dụng:$branchId = app('branch_id');
       **/
      app()->instance('branch_id', $request->header('karinox-branch-id'));
    }

    return $next($request);
  }
}
