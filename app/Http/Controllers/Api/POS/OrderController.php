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
  public function index(Request $request)
  {
      $tableId = $request->input('table_id');

      if (!$tableId) {
          return response()->json(['error' => 'Table ID is required'], 400);
      } 
      $order = $this->orderService->getOrderByTableId($tableId);
      return new OrderResource($order);
  }

  public function getOrderByTableId(Request $request)
  {
      $tableId = $request->input('table_id');

      if (!$tableId) {
          return response()->json(['error' => 'Table ID is required'], 400);
      } 
      return response()->json(['table_id' => $tableId]);
      $order = $this->orderService->getOrderByTableId($tableId);
      return new OrderResource($order);
  }
  /**
   * Đặt trước, trạng thái sẽ là PENDING
   */
  public function preOrder(OrderRequest $request): JsonResponse
  {
    $data = $request->validated();
    dd($data);
    return response()->json($order, 201);
  }

  /**
   * Cập nhật đơn hàng
   */
  public function order(OrderRequest $request, int $orderId): JsonResponse
  {
    $validatedData = $request->validated();

    $order = $this->orderService->updateOrder(
      $orderId,
      $validatedData['order_items'] ?? [],
      $validatedData['table_id'] ?? null,
      $validatedData['voucher_code'] ?? null,
      $validatedData['note'] ?? '',
      $validatedData['status'] ?? OrderStatus::PENDING
    );

    return response()->json($order);
  }
}
