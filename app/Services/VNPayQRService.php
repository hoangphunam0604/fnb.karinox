<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class VNPayQRService
{
  protected string $endpoint;
  protected string $appId;
  protected string $merchantName;
  protected string $merchantCode;
  protected string $terminalId;
  protected string $masterMerCode;
  protected string $merchantType;
  protected string $serviceCode;
  protected string $secretKeyGen;
  protected string $secretKeyCheck;
  protected string $secretKeyRefund;

  public function __construct()
  {
    $this->endpoint = config('vnpayqr.endpoint');
    $this->appId = config('vnpayqr.app_id');
    $this->merchantName = config('vnpayqr.merchant_name');
    $this->merchantCode = config('vnpayqr.merchant_code');
    $this->terminalId = config('vnpayqr.terminal_id');
    $this->masterMerCode = config('vnpayqr.master_mer_code');
    $this->merchantType = config('vnpayqr.merchant_type');
    $this->secretKeyGen = config('vnpayqr.secret_key_gen');
    $this->secretKeyCheck = config('vnpayqr.secret_key_check');
    $this->secretKeyRefund = config('vnpayqr.secret_key_refurn');
  }

  public function createQRCode(string $orderCode, int $amount): array
  {

    $expireTime = now()->addMinutes(10)->format('ymdHi');
    $payload = [
      'appId' => $this->appId,
      'merchantName' => $this->merchantName,
      'serviceCode' => '03', //Mã dịch vụ QR. Giá trị mặc định là 03
      'countryCode' => 'VN',
      'masterMerCode' => $this->masterMerCode,
      'merchantType' => $this->merchantType,
      'merchantCode' => $this->merchantCode,
      'terminalId' => $this->terminalId,
      'payType' => '03', //Mã dịch vụ QR. Giá trị mặc định là 03
      'productId' => '',
      'tipAndFee' => '',
      'txnId'   => $orderCode,
      'billNumber' => $orderCode,
      'amount' => (string)$amount,
      'ccy' => '704',
      'expDate' => $expireTime,
      'desc' => '',
    ];
    $payload['checksum'] = $this->checksumGen($payload);

    $client = new Client();
    $response = $client->request('POST', $this->endpoint, [
      'headers' => [
        'Content-Type' => 'text/plain',
      ],
      'body' => json_encode($payload),
      'verify' => false // Nếu cần tắt SSL dev
    ]);
    $responseData = json_decode($response->getBody(), true);
    $checksum_return = $this->checksumGenFromResponse($responseData);
    if ($checksum_return !== $responseData['checksum'])
      return ['status' => false, "data-qr" => "", "message"  =>  "Dữ liệu trả về không hợp lệ"];
    if ($responseData['code' !== "00"])
      return ['status' => false, "data-qr" => "", "message"  =>  $responseData['message']];
    return ['status' => true, "data-qr" => $responseData['data'], "message"  =>  $responseData['message']];
  }

  public function checkIPN($payload) {
    
  }

  private function checksumGen($payload)
  {
    // Tính checksum
    $data = implode('|', [
      $payload['appId'],
      $payload['merchantName'],
      $payload['serviceCode'],
      $payload['countryCode'],
      $payload['masterMerCode'],
      $payload['merchantType'],
      $payload['merchantCode'],
      $payload['terminalId'],
      $payload['payType'],
      $payload['productId'],
      $payload['txnId'],
      $payload['amount'],
      $payload['tipAndFee'],
      $payload['ccy'],
      $payload['expDate'],
      $this->secretKeyGen,
    ]);
    return strtoupper(md5($data));
  }

  private function checksumGenFromResponse($payload)
  {
    // Tính checksum
    $data = implode('|', [
      $payload['code'],
      $payload['message'],
      $payload['data'],
      $payload['url'],
      $this->secretKeyGen,
    ]);
    return strtoupper(md5($data));
  }
}
