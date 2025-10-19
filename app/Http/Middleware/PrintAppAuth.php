<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Branch;
use Illuminate\Support\Facades\Log;

class PrintAppAuth
{
  /**
   * Handle an incoming request.
   * 
   * Middleware cho ứng dụng Print Management - không cần đăng nhập
   * Chỉ cần X-Branch-ID để xác định chi nhánh làm việc
   */
  public function handle(Request $request, Closure $next): Response
  {
    // Lấy branch_id từ header hoặc request
    $branchId = $request->header('X-Branch-ID') ?: $request->get('branch_id');

    // Validation branch_id
    if (!$branchId) {
      return response()->json([
        'success' => false,
        'message' => 'Missing X-Branch-ID header. Please select a branch first.',
        'code' => 'BRANCH_REQUIRED'
      ], 400);
    }

    // Kiểm tra branch có tồn tại không
    $branch = Branch::find($branchId);
    if (!$branch) {
      return response()->json([
        'success' => false,
        'message' => 'Invalid branch ID. Branch not found.',
        'code' => 'BRANCH_NOT_FOUND'
      ], 404);
    }

    // Kiểm tra branch có active không
    if (!$branch->is_active) {
      return response()->json([
        'success' => false,
        'message' => 'Branch is not active.',
        'code' => 'BRANCH_INACTIVE'
      ], 403);
    }

    // Set branch context cho request
    $request->merge([
      'current_branch_id' => $branchId,
      'current_branch' => $branch
    ]);

    // Log access cho monitoring
    Log::info('Print app access', [
      'branch_id' => $branchId,
      'branch_name' => $branch->name,
      'endpoint' => $request->path(),
      'method' => $request->method(),
      'ip' => $request->ip(),
      'user_agent' => $request->userAgent()
    ]);

    return $next($request);
  }
}
