<?php

namespace App\Http\PaymentGateway\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentGateways\CashGateway;

class CashController extends Controller
{
  protected CashGateway $service;

  public function __construct(CashGateway $service)
  {
    $this->service = $service;
  }

  /**
   * Xác nhận thanh toán tiền mặt tại quầy.
   * Gọi khi thu ngân bấm "Xác nhận đã thu tiền".
   * Print jobs sẽ được tự động tạo thông qua OrderCompleted event listener.
   */
  public function confirm(string $code)
  {
    $order = Order::where('order_code', $code)->firstOrFail();

    $payStatus = $this->service->pay($order);

    if ($payStatus) {
      return response()->json([
        'status' => true,
        'message' => 'Thanh toán thành công',
        'order' => [
          'id' => $order->id,
          'order_code' => $order->order_code,
          'table_name' => $order->table?->name,
          'payment_method' => $order->payment_method,
        ]
      ]);
    }

    return response()->json(['status' => false, 'message' => 'Internal error']);
  }
}
