<?php

namespace App\Http\POS\Controllers;

use App\Http\Common\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Services\OrderService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use App\Http\POS\Resources\PrintInvoiceResource;
use App\Http\POS\Resources\PrintProvisionalResource;
use App\Http\POS\Resources\PrintKitchenResource;
use App\Http\POS\Resources\PrintLabelResource;
use App\Models\Order;

class PrintController extends Controller
{

  public function __construct(protected OrderService $orderService, protected InvoiceService $invoiceService) {}

  /**
   * Lấy dữ liệu in tạm tính
   * POST /api/pos/print/{order}/provisional
   */
  public function provisional($order): JsonResponse
  {
    try {
      $order = $this->orderService->findByCode($order);

      // Load relationships needed for printing
      $order->load(['items.product', 'items.toppings', 'user']);

      // Transform data using Resource
      $printData = new PrintProvisionalResource($order);

      return response()->json([
        'success' => true,
        'message' => 'Lấy dữ liệu in tạm tính thành công',
        'data' => $printData
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Lỗi lấy dữ liệu in tạm tính: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Lấy dữ liệu in phiếu bếp
   * POST /api/pos/print/{order}/kitchen
   */
  public function kitchen(Order $order): JsonResponse
  {
    try {
      // Load relationships needed for printing
      $order->load(['kitchenItems.product', 'kitchenItems.toppings', 'user']);

      if ($order->kitchenItems->isEmpty()) {
        return response()->json([
          'success' => false,
          'message' => 'Đã báo rồi hoặc không có món nào cần báo bếp'
        ], 400);
      }

      // Transform data using Resource
      $printData = new PrintKitchenResource($order);

      return response()->json([
        'success' => true,
        'message' => 'Lấy dữ liệu in phiếu bếp thành công',
        'data' => $printData
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Lỗi lấy dữ liệu in phiếu bếp: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Lấy dữ liệu in hóa đơn
   * POST /api/pos/print/{order}/invoice
   * Note: order parameter có thể là orderCode hoặc invoiceCode
   */
  public function invoice(Order $order): JsonResponse
  {
    try {
      // Thử tìm theo invoice code trước, nếu không có thì tìm theo order code
      try {
        $invoice = $order->invoice();
      } catch (\Exception $e) {
        // Nếu không tìm thấy invoice, thử tìm order và lấy invoice từ order
        $orderModel = $this->orderService->findByCode($order);
        if (!$orderModel->invoice) {
          return response()->json([
            'success' => false,
            'message' => 'Order chưa có hóa đơn'
          ], 404);
        }
        $invoice = $orderModel->invoice;
      }

      // Load relationships needed for printing
      $invoice->load(['items.product', 'items.toppings', 'user', 'customer']);

      // Transform data using Resource
      $printData = new PrintInvoiceResource($invoice);

      return response()->json([
        'success' => true,
        'message' => 'Lấy dữ liệu in hóa đơn thành công',
        'data' => $printData
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Lỗi lấy dữ liệu in hóa đơn: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Lấy dữ liệu in tem/nhãn cho sản phẩm
   * POST /api/pos/print/{order}/label
   */
  public function label(Order $order, Request $request): JsonResponse
  {
    try {
      $request->validate([
        'itemIds' => 'array',
        'itemIds.*' => 'integer'
      ]);

      // Load items with relationships
      $order->load(['items.product', 'items.toppings']);

      // Filter items if itemIds provided, otherwise print all items
      $items = $request->has('itemIds')
        ? $order->items->whereIn('id', $request->itemIds)
        : $order->items;

      if ($items->isEmpty()) {
        return response()->json([
          'success' => false,
          'message' => 'Không có món nào để in tem'
        ], 400);
      }

      // Transform data using Resource
      $printData = new PrintLabelResource($items);

      return response()->json([
        'success' => true,
        'message' => 'Lấy dữ liệu in tem thành công',
        'data' => $printData
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Lỗi lấy dữ liệu in tem: ' . $e->getMessage()
      ], 500);
    }
  }


  /**
   * Lấy dữ liệu in kê tiền
   * POST /api/pos/print/{order}/cash-inventory
   */
  public function cashInventory($order, Request $request): JsonResponse
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

      // Lấy thông tin order nếu cần
      $orderModel = $this->orderService->findByCode($order);

      $payload = array_merge($request->all(), [
        'order_code' => $order,
        'order_id' => $orderModel->id
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Lấy dữ liệu in kê tiền thành công',
        'data' => $payload
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Lỗi lấy dữ liệu in kê tiền: ' . $e->getMessage()
      ], 500);
    }
  }
}
