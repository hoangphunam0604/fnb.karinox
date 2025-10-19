<?php

namespace App\Http\Controllers\Payments;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentGateways\InfoPlusGateway;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class InfoPlusController extends Controller
{
  protected InfoPlusGateway $service;

  public function __construct(InfoPlusGateway $service)
  {
    $this->service = $service;
  }
  public function getQrCode(string $code)
  {
    return DB::transaction(function () use ($code) {
      return Cache::remember("infoplus_qr_code_{$code}", 20, function () use ($code) {
        $order = Order::where('code', $code)->firstOrFail();
        $paymentData =  $this->service->createQRCode($order);
        $order->payment_started_at = now();
        $order->payment_url = $paymentData['qrCode'];
        $order->save();
        return $paymentData;
      });
      return $paymentData;
    });
  }

  /**
   * IPN xác thực từ VNPAY: VNPAY gọi về khi KH đã thanh toán
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
      $code = $data['data']['transactionUuid'];
      $order  = Order::where('code', $code)->first();

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

  private function responseData(string $code, $msg)
  {
    return response()->json([
      "responseCd"  => $code,
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
