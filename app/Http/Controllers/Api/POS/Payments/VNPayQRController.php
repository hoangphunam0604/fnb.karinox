<?php

namespace App\Http\Controllers\Api\POS\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\VNPayQRService;
use Illuminate\Http\Request;

class VNPayQRController extends Controller
{
  public function create(Request $request, VNPayQRService $vnpayQRService)
  {
    $order = Order::where('order_code', $request->input('order_code'))->firstOrFail();
    return $vnpayQRService->createQRCode($order->order_code, $order->total_price);
  }

  /**
   * IPN xác thực từ VNPAY: VNPAY gọi về khi KH đã thanh toán
   */
  public function ipn(Request $request, VNPayQRService $vnpayQRService, OrderService $orderService)
  {
    $checkIPN = $vnpayQRService->checkIPN($request->all());
    // IPN theo tài liệu Merchant Payment:
    if ($request->input('code') === '00') {
      $order = Order::where('code', $request->input('txnId'))->firstOrFail();
      $amount = (int) $request->input('amount');

      if ($order->payment_status !== 'paid') {
        $orderService->completeOrder($order, $amount);
      }

      return response()->json(['code' => '00', 'message' => 'OK']);
    }

    return response()->json(['code' => '99', 'message' => 'Failed'], 400);
  }
}
