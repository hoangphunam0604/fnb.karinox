<?php

namespace App\Http\Controllers\Api\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\POS\OrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
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
