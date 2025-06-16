<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

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

  public function createQRCode(string $orderCode, int $amount, string $expireTime): array
  {
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
      'billNumber' => $orderCode,
      'amount' => (string)$amount,
      'ccy' => '704',
      'expDate' => $expireTime,
      'desc' => '',
    ];

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
      '',
      $payload['ccy'],
      $payload['expDate'],
      $this->secretKeyGen,
    ]);

    $payload['checksum'] = strtoupper(md5($data));

    $response = Http::withHeaders([
      'Content-Type' => 'text/plain',
    ])->post($this->endpoint, json_encode($payload));

    return $response->json();
  }
}
