<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentGateways\VNPayQRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VNPayController extends Controller
{
  protected VNPayQRService $service;

  public function __construct(VNPayQRService $service)
  {
    $this->service = $service;
  }
  public function getQrCode(string $code)
  {
    $order = Order::where('code', $code)->firstOrFail();

    $paymentData =  $this->service->createQRCode($order->code, $order->total_price);

    $order->payment_started_at = now();
    $order->payment_url = $paymentData['qrCode'];
    $order->save();

    return $paymentData;
  }

  /**
   * IPN xác thực từ VNPAY: VNPAY gọi về khi KH đã thanh toán
   */
  public function callback(Request $request)
  {
    try {
      Log::info($request->getContent());
      Log::info(json_encode($request->all()));
      $data = json_decode($request->getContent(), true);
      if (!$data)
        return response()->json(['code' => '11', 'message' => 'Format data is wrong']);

      $checksum = $data['checksum'] ?? null;
      $verifyChecksum = $this->service->checksumIPN($data);
      if ($checksum !== $verifyChecksum)
        return response()->json(['code' => '01', 'message' => 'Checksum is wrong.',]);

      $code = $data['code'] ?? null;
      $msgType = $data['msgType'] ?? null;
      $txnId = $data['txnId'] ?? null;
      $amount = $data['amount'] ?? null;

      if ($code !== '00' && $msgType !== '1')
        return response()->json(['code' => '02',  'message' => 'Invalid payment status']);

      $order = Order::where('code', $txnId)->first();
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
      $payStatus = $this->service->pay($order);
      if ($payStatus) {
        return response()->json([
          'code' => '00',
          'message' => 'Success.',
          'data' => ['txnId' => $order->code]
        ]);
      }
      return response()->json(['code' => '99', 'message' => 'Internal error']);
    } catch (\Exception $e) {
      return response()->json(['code' => '99', 'message' => 'Internal error']);
    }
  }
}
