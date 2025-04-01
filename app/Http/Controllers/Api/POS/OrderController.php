<?php

namespace App\Http\Controllers\Api\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\POS\OrderRequest;
use App\Http\Resources\Api\POS\OrderResource;
use Illuminate\Http\JsonResponse;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Enums\OrderStatus;
use App\Http\Resources\Api\POS\OrderPrintResource;
use App\Services\PointService;

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
    $data = $request->only(["customer_id", "note", "items", 'voucher_code', 'reward_points_used', 'payment_method']);
    $order = $this->orderService->updateOrder($order_id, $data);
    return new OrderResource($order);
  }
  public function checkout($order_id)
  {
    $order = $this->orderService->markAsCompleted($order_id);
    return new OrderResource($order);
  }
  public function cancel($order_id)
  {
    $result = $this->orderService->cancelOrder($order_id);
    return response()->json($result);
  }
  public function removeCustomer($order_id)
  {
    $order = $this->orderService->removeCustomer($order_id);
    return new OrderResource($order);
  }

  public function removeRewardPointsUsed($order_id)
  {
    $order = $this->orderService->removeRewardPointsUsed($order_id);
    return new OrderResource($order);
  }
  public function removeVoucherUsed($order_id)
  {
    $order = $this->orderService->removeVoucherUsed($order_id);
    return new OrderResource($order);
  }

  public function notifyKitchen($orderId)
  {
    $order = $this->orderService->notifyKitchen($orderId);
    return new OrderPrintResource($order);
  }
  public function provisional($orderId)
  {
    $order = $this->orderService->findOrderById($orderId);
    return new OrderPrintResource($order);
  }
}
