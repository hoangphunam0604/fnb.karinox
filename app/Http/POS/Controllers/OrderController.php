<?php

namespace App\Http\POS\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\POS\Resources\OrderResource;
use App\Services\OrderService;
use App\Events\PrintRequested;
use App\Http\POS\Responses\ApiResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
  protected OrderService $orderService;

  public function __construct(OrderService $orderService)
  {
    $this->orderService = $orderService;
  }

  public function getOrderByTableId($tableId, Request $request)
  {
    $branchId = app()->bound('karinox_branch_id') ? app('karinox_branch_id') : $request->query('branch_id');

    if (!$branchId)
      return response()->json(['error' => 'Hãy chọn chi nhánh'], 400);

    if (!$tableId)
      return response()->json(['error' => 'Hãy chọn bàn'], 400);

    $orders = $this->orderService->getOrdersByTableId($branchId, $tableId);
    return  OrderResource::collection($orders);
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

  public function cancel($order_id)
  {
    $this->orderService->cancelOrder($order_id);
    return ApiResponse::success('Đã huỷ đặt hàng');
  }

  public function extend($order_id, Request $request)
  {
    $order = $this->orderService->extend($order_id, $request->old_code);
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

  /**
   * Gửi yêu cầu in phiếu bếp
   */
  public function notifyKitchen($orderId)
  {
    $order = $this->orderService->findOrderById($orderId);
    if ($order->kitchenItems->isEmpty()) {
      return ApiResponse::error('Đã báo rồi hoặc không có món nào cần báo bếp');
    }

    broadcast(new PrintRequested('order-kitchen', ['id' => $order->id], $order->branch_id));
  }
}
