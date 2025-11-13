<?php

namespace App\Http\PaymentGateway\Controllers;

use App\Enums\PaymentStatus;
use App\Http\Common\Controllers\Controller;
use App\Http\PaymentGateway\Requests\PayRequest;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentGateways\InfoPlusGateway;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InfoPlusController extends Controller
{

  public function __construct(protected InfoPlusGateway $service, protected OrderService $order)
  {
    $this->service = $service;
  }

  public function pay(PayRequest $request)
  {
    $order_code = $request->order_code;
    $qrData = Cache::remember("infoplus_qr_{$order_code}", now()->addMinutes(60), function () use ($order_code) {
      $order = $this->order->findByCode($order_code);
      return $this->service->generateQrString($order);
    });
    return response()->json([
      'success'  =>  true,
      'data'  => $qrData
    ]);
  }

  /**
   * IPN xác thực thanh toán từ InfoPlus
   */
  public function mock(string $order_code)
  {
    $order  = Order::where('order_code', $order_code)->first();
    $this->service->pay($order);
    return response()->json([
      'success'  =>  true,
      'message' => 'Thanh toán thành công (Mock)'
    ]);
  }

  /**
   * IPN xác thực thanh toán từ InfoPlus
   */
  public function callback(Request $request)
  {
    try {
      $signature = $request->header('CMS-RSA-Signature');
      $rawBody = $request->getContent();

      $verifySign = $this->service->verifySign($rawBody, $signature);

      if (!$verifySign)
        return $this->responseData("000001", "Chữ ký không hợp lệ.");

      $data = json_decode($rawBody, true);
      $order_code = $data['data']['transactionUuid'];
      $order  = Order::where('order_code', $order_code)->first();

      if (!$order)
        return $this->responseData('C00099', 'Giao dịch không tồn tại.');

      if ($order->payment_status == PaymentStatus::PAID)
        return $this->responseData('000000', 'OK.');

      $payStatus = $this->service->pay($order);

      if ($payStatus) {
        return $this->responseData('000000', 'OK.');
      }

      return $this->responseData("000001", "Server ERROR");
    } catch (\Exception $e) {
      return $this->responseData("000001", "Server ERROR");
    }
  }

  private function responseData(string $order_code, $msg)
  {
    return response()->json([
      "responseCd"  => $order_code,
      "responseMsg" => $msg,
      "responseTs"  =>  Carbon::now()->format('Y-m-d H:i:s v'),
      "responseTraceId" => null,
      "requesterTrId" => $this->generateDigitString()
    ]);
  }

  function generateDigitString(int $length = 27): string
  {
    $digits = '';
    $current = 0;

    for ($i = 0; $i < $length; $i++) {
      $digits .= $current;
      $current = ($current + 1) % 10; // quay vòng từ 9 về 0
    }

    return $digits;
  }
}
