<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentGateways\CashGateway;
use Illuminate\Http\Request;

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
    $order = Order::where('code', $code)->firstOrFail();

    $payStatus = $this->service->pay($order);

    if ($payStatus) {
      return response()->json([
        'status' => true,
        'message' => 'Thanh toán thành công',
        'data' => [
          'order_code' => $order->code,
          'payment_method' => 'cash'
        ]
      ]);
    }

    return response()->json(['status' => false, 'message' => 'Internal error']);
  }
}
