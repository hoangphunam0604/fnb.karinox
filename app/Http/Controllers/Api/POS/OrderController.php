<?php

namespace App\Http\Controllers\Api\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\POS\OrderRequest;
use App\Http\Resources\Api\POS\OrderResource;
use Illuminate\Http\JsonResponse;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Enums\OrderStatus;
use App\Http\Resources\Api\POS\OrderItemPrintResource;
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
    [$order, $allItems, $kitchenItems, $labels] = $this->orderService->notifyKitchen($orderId);
    return response()->json([
      'order' => new OrderResource($order->setRelation('items', $allItems->values())),
      'print_data' => [
        'labels' => OrderItemPrintResource::collection($labels),
        'kitchen' => $kitchenItems->count() > 0 ? new OrderPrintResource(
          $order->setRelation('items', $kitchenItems->values())
        ) : null,
      ]
    ]);
  }
  public function provisional($orderId)
  {
    $order = $this->orderService->findOrderById($orderId);
    return new OrderPrintResource($order);
  }
  public function checkout($orderId)
  {
    [$order, $kitchenItems, $labels] = $this->orderService->markAsCompleted($orderId);
    return response()->json([
      'order' => new OrderPrintResource($order),
      'print_data' => [
        'invoice' => new OrderPrintResource($order),
        'labels' => OrderItemPrintResource::collection($labels),
        'kitchen' => $kitchenItems ? new OrderPrintResource(
          $order->setRelation('items', $kitchenItems->values())
        ) : null,
      ]
    ]);
    return new OrderResource($order);
  }
  public function payment($order_id)
  {
    $order = $this->orderService->payment($order_id);
    return new OrderResource($order);
  }
  public function cancel($order_id)
  {
    $result = $this->orderService->cancelOrder($order_id);
    return response()->json($result);
  }
}
