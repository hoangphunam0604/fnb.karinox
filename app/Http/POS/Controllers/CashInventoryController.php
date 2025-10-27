<?php

namespace App\Http\POS\Controllers;

use App\Events\PrintRequested;
use App\Http\Common\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CashInventoryController extends Controller
{
  /**
   * Store cash inventory and trigger print request
   */
  public function store(Request $request): JsonResponse
  {
    try {
      // Get branch ID using the karinox_branch_id binding or from query parameter
      $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : $request->query('branch_id');

      if (!$branchId) {
        return response()->json([
          'success' => false,
          'message' => 'Branch ID is required'
        ], 400);
      }

      // Get all request data as payload
      $payload = $request->all();
      // Trigger print request event with full payload
      event(new PrintRequested('cash-inventory', $payload, $branchId));

      return response()->json([
        'success' => true,
        'message' => 'Cash inventory print request sent successfully',
        'data' => $payload
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'An error occurred while processing cash inventory',
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
