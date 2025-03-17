<?php

namespace App\Http\Controllers\Api\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\POS\OrderRequest;
use App\Http\Resources\Api\POS\OrderResource;
use Illuminate\Http\JsonResponse;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Enums\OrderStatus;

class OrderController extends Controller
{
  protected OrderService $orderService;

  public function __construct(OrderService $orderService)
  {
    $this->orderService = $orderService;
  }


  public function getOrderByTableId(Request $request)
  {
    $tableId = $request->input('table_id');

    if (!$tableId) {
      return response()->json(['error' => 'Table ID is required'], 400);
    }
    $order = $this->orderService->getOrderByTableId($tableId);
    return new OrderResource($order);
  }

  public function update($order_id, Request $request,)
  {
    $data = $request->only(["customer_id", "note", "items", 'voucher_code', 'reward_points_used']);
    $order = $this->orderService->updateOrder($order_id, $data);
    return response()->json($order);
  }
}
