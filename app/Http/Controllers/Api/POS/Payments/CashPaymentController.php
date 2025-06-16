<?php

namespace App\Http\Controllers\Api\POS\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class CashPaymentController extends Controller
{
  /**
   * Xác nhận thanh toán tiền mặt tại quầy.
   * Gọi khi thu ngân bấm "Xác nhận đã thu tiền".
   */
  public function confirm(Request $request, OrderService $orderService)
  {
    $order = Order::where('code', $request->input('order_code'))->firstOrFail();

    $orderService->completeOrder($order);

    return response()->json([
      'message' => 'Thanh toán tiền mặt thành công',
      'order' => $order
    ]);
  }
}
