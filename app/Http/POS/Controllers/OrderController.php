<?php

namespace App\Http\POS\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\POS\Resources\OrderResource;
use App\Services\OrderService;
use App\Services\VoucherService;
use App\Http\POS\Responses\ApiResponse;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{

  public function __construct(
    protected OrderService $orderService,
    protected VoucherService $voucherService
  ) {}

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
    $data = $request->only(["note", "items"]);
    $order = $this->orderService->updateOrder($order_id, $data);
    return new OrderResource($order);
  }

  public function addCustomer(Order $order, $customer_id)
  {
    $order = $this->orderService->addCustomer($order, $customer_id);
    return new OrderResource($order);
  }

  public function removeCustomer(Order $order)
  {
    $order = $this->orderService->removeCustomer($order);
    return new OrderResource($order);
  }

  public function usePoint(Order $order, Request $request)
  {
    // Lấy danh sách order_item_ids từ request, nếu không có thì áp dụng cho tất cả
    $orderItemIds = $request->input('order_item_ids', null);

    $order = $this->orderService->applyPoint($order, $orderItemIds);
    return new OrderResource($order);
  }

  public function removePoint(Order $order, Request $request)
  {
    // Lấy danh sách order_item_ids từ request, nếu không có thì xóa tất cả
    $orderItemIds = $request->input('order_item_ids', null);

    $order = $this->orderService->removePoint($order, $orderItemIds);
    return new OrderResource($order);
  }
  public function removeVoucher(Order $order)
  {
    $order = $this->orderService->removeVoucher($order);
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
      'table_id' => 'integer|exists:tables,id',
      'split_items' => 'required|array',
      'split_items.*' => 'integer|min:1',
    ]);

    [$updatedOrder, $newOrder] = $this->orderService->splitOrder($orderID, $data['table_id'], $data['split_items']);

    return response()->json([
      'message' => 'Tách đơn thành công',
      'updated_order' => new OrderResource($updatedOrder),
      'new_order' =>  new OrderResource($newOrder),
    ]);
  }
}
