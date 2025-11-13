<?php

namespace App\Http\PaymentGateway\Controllers;

use App\Http\Common\Controllers\Controller;
use App\Http\PaymentGateway\Requests\PayRequest;
use App\Models\Order;
use App\Services\PaymentGateways\CardGateway;

class CardController extends Controller
{
  protected CardGateway $service;

  public function __construct(CardGateway $service)
  {
    $this->service = $service;
  }

  /**
   * Xác nhận thanh toán thẻ tại quầy.
   * Gọi khi thu ngân bấm "Xác nhận đã thu tiền".
   * Print jobs sẽ được tự động tạo thông qua OrderCompleted event listener.
   */
  public function pay(PayRequest $request)
  {
    $order = Order::where('order_code', $request->order_code)->firstOrFail();
    $payStatus = $this->service->pay($order);
    if ($payStatus) {
      return response()->json([
        'success' => true,
        'message' => 'Thanh toán thành công',
        'order' => [
          'id' => $order->id,
          'order_code' => $order->order_code,
          'table_name' => $order->table?->name,
          'payment_method' => $order->payment_method,
        ]
      ]);
    }

    return response()->json(['success' => false, 'message' => 'Internal error']);
  }
}
