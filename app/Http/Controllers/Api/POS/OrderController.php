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
      return response()->json(['error' => 'Hãy chọn bàn'], 400);
    }
    $orders = $this->orderService->getOrdersByTableId($tableId);
    return  OrderResource::collection($orders);
  }

  public function update($order_id, Request $request,)
  {
    $data = $request->only(["customer_id", "note", "order_items", 'voucher_code', 'reward_points_used', 'payment_method']);
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
    [$order, $kitchenItems, $labels] = $this->orderService->notifyKitchen($orderId);
    return response()->json([
      'success'  =>  true,
      "data"  =>  [
        'order' => new OrderResource($order),
        'print_data' => [
          'labels' => OrderItemPrintResource::collection($labels),
          'kitchen' => $kitchenItems->count() > 0 ? new OrderPrintResource(
            tap(clone $order)->setRelation('items', $kitchenItems->values())
          ) : null,
        ]
      ]
    ]);
  }
  public function provisional($orderId)
  {
    $order = $this->orderService->findOrderById($orderId);
    return new OrderPrintResource($order);
  }
  public function getPrintData($orderId)
  {
    [$order, $kitchenItems, $labels] = $this->orderService->getPrintData($orderId);
    return response()->json([
      'success'  =>  true,
      'data'  =>  [
        'order' => new OrderResource($order),
        'print_data' => [
          'invoice' => new OrderPrintResource($order),
          'labels' => OrderItemPrintResource::collection($labels),
          'kitchen' => $kitchenItems->count() > 0 ? new OrderPrintResource(
            tap(clone $order)->setRelation('items', $kitchenItems->values())
          ) : null,
        ]
      ]
    ]);
    return new OrderResource($order);
  }
  public function cancel($order_id)
  {
    $result = $this->orderService->cancelOrder($order_id);
    return response()->json($result);
  }

  public function extend($order_id, Request $request)
  {
    $order = $this->orderService->extend($order_id, $request->old_order_code);
    return new OrderResource($order);
  }

  public function split(Request $request,  $orderID)
  {
    $data = $request->validate([
      'split_items' => 'required|array',
      'split_items.*' => 'integer|min:1',
    ]);

    [$updatedOrder, $newOrder] = $this->orderService->splitOrder($orderID, $data['split_items']);

    return response()->json([
      'message' => 'Tách đơn thành công',
      'updated_order' => new OrderResource($updatedOrder),
      'new_order' =>  new OrderResource($newOrder),
    ]);
  }

  public function getVNPayQrCode($orderID) {}
}
