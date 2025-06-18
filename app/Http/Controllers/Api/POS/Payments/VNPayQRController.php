<?php

namespace App\Http\Controllers\Api\POS\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\VNPayQRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VNPayQRController extends Controller
{
  public function create(Request $request, VNPayQRService $vnpayQRService)
  {
    $order = Order::where('order_code', $request->input('order_code'))->firstOrFail();

    $paymentData = $vnpayQRService->createQRCode($order->order_code, $order->total_price);

    $order->payment_started_at = now();
    $order->payment_url = $paymentData['data-qr'];
    $order->save();

    return $paymentData;
  }

  /**
   * IPN xác thực từ VNPAY: VNPAY gọi về khi KH đã thanh toán
   */
  public function ipn(Request $request, VNPayQRService $vnpayQRService, OrderService $orderService)
  {
    try {
      Log::info($request->getContent());
      Log::info(json_encode($request->all()));
      $data = json_decode($request->getContent(), true);
      if (!$data)
        return response()->json(['code' => '11', 'message' => 'Format data is wrong']);

      $checksum = $data['checksum'] ?? null;
      $verifyChecksum = $vnpayQRService->checksumIPN($data);
      if ($checksum !== $verifyChecksum)
        return response()->json(['code' => '01', 'message' => 'Checksum is wrong.',]);

      $code = $data['code'] ?? null;
      $msgType = $data['msgType'] ?? null;
      $txnId = $data['txnId'] ?? null;
      $amount = $data['amount'] ?? null;

      if ($code !== '00' && $msgType !== '1')
        return response()->json(['code' => '02',  'message' => 'Invalid payment status']);

      $order = Order::where('order_code', $txnId)->first();
      if (!$order)
        return response()->json(['code' => '01', 'message' => 'Order not found']);

      if ($order->total_price != $amount)
        return response()->json([
          'code' => '07',
          'message' => 'Incorrect amount',
          'data'  =>  [
            'amount'  => (string)$order->total_price
          ]
        ]);

      $orderService->pay($order);

      return response()->json(['code' => '00', 'message' => 'Success.', 'data' => ['txnId' => $order->order_code]]);
    } catch (\Exception $e) {
      return response()->json(['code' => '99', 'message' => 'Internal error']);
    }
  }
}
