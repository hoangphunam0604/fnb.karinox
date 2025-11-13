<?php

namespace App\Services\PaymentGateways;

use App\Enums\PaymentStatus;
use App\Models\Order;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InfoPlusGateway extends BaseGateway
{

  private $userId;
  private $clientId;
  private $secretKey;
  private $privateKey;
  private $publicKey;
  private $bankCode;

  private $posUniqueId; //Sử dụng để phân biệt server
  private $posFranchiseeName;
  private $posCompanyName;


  //Http client
  private $client;
  private $base_url;
  private $endpoint_login = "/ocms/v1/auth/login";
  private $endpoint_create_qr_code = "/ocms/v3/ec/po_create_qr";

  private $userAgent = "Karinox FNB";
  public function __construct()
  {
    parent::__construct();

    $this->userId = config('infoplus.userId');
    $this->clientId = config('infoplus.clientId');
    $this->secretKey = config('infoplus.secretKey');
    $this->privateKey = config('infoplus.privateKey');
    $this->publicKey = config('infoplus.publicKey');

    $this->bankCode = config('infoplus.bankCode');
    $this->posUniqueId = config('infoplus.posUniqueId');
    $this->posFranchiseeName = config('infoplus.posFranchiseeName');
    $this->posCompanyName = config('infoplus.posCompanyName');

    $this->base_url = config('infoplus.base_url');
    $this->client = new Client();
  }

  /**
   * Return the payment method identifier for this gateway.
   *
   * @return string
   */

  protected function getPaymentMethod(): string
  {
    return 'infoplus';
  }

  /**
   * Tạo mã QR
   * 
   * @param string $barcode Mã giao dịch
   * @param float $amount Số tiền cần thanh toán
   * @return array Thông tin thanh toán
   */
  public function generateQrString(Order $order)
  {
    $qrData = $this->getQrDataFromInfoPlus($order);
    DB::transaction(function () use ($order, $qrData) {
      $order->payment_method = $this->getPaymentMethod();
      $order->payment_status = PaymentStatus::PENDING;
      $order->payment_started_at = now();
      $order->payment_url = $qrData;
      $order->save();
    });
    return $qrData;
  }


  private function getQrDataFromInfoPlus(Order $order)
  {
    $payload = [
      'data'  =>  [
        'transactionUuid' => $order->order_code,
        'depositAmt' => (int) $order->total_price,
        'posUniqueId' => $this->posUniqueId,
        'posFranchiseeName' => $this->posFranchiseeName,
        'posCompanyName' => $this->posCompanyName,
        'posBillNo' => $order->id,
        'remark' => "Thanh Toan Don Hang {$order->order_code}"
      ]
    ];

    $json_payload = $this->json_minify($payload);

    $cmsSignature = $this->sign($json_payload);
    $token = $this->getAccessToken();

    $headers = [
      'Content-Type'  =>  'application/json',
      'token' => $token['accessToken'],
      "bankCode"  =>  $this->bankCode,
      'CMS-RSA-Signature'   => $cmsSignature,
    ];

    // Gửi với Guzzle
    $response = $this->client->request('POST', $this->base_url . $this->endpoint_create_qr_code, [
      'headers' => $headers,
      'body' => $json_payload,
      'verify' => false // Nếu cần tắt SSL dev
    ]);
    Log::info('InfoPlus Create QR Response: ' . $response->getBody());
    $data = json_decode($response->getBody(), true);
    if (!isset($data['responseCd']) || $data['responseCd'] != "000000" || !isset($data['data']['qrData'])):
      throw new \RuntimeException('Lỗi khi tạo mã QR');
    endif;
    return $data['data']['qrData'];
  }
  private function getAccessToken(): array
  {
    return Cache::remember("infoplus_access_token", 20, function () {
      $headers = [
        'Content-Type'  =>  'application/json',
        'User-Agent' => $this->userAgent,
        'Authorization' => $this->getHmacAuthHeader(),
        'AuthorizationHeaderParameters'      => $this->getClientKeyBase64(),
        "bankCode"  =>  $this->bankCode
      ];

      $payload = [
        'data'  =>  [
          'masterId' =>  $this->userId,
          'clientId'  => $this->clientId
        ]
      ];
      $response = $this->client->request('POST', $this->base_url . $this->endpoint_login, [
        'headers' => $headers,
        'body' => json_encode($payload),
        'verify' => false // Nếu cần tắt SSL dev
      ]);

      $data = json_decode($response->getBody(), true);
      if (isset($data['responseCd']) && $data['responseCd'] == '000000') {
        return [
          'time'  =>  Carbon::now()->format('Y-m-d H:i:s v'),
          'accessToken'  => $data['data']['accessToken']
        ];
      }
      abort(403, $data['responseMsg'] ?? "Lỗi kết nối tới InfoPlus");
    });
  }

  private function todayYmd(): string
  {
    $tz = new \DateTimeZone('Asia/Ho_Chi_Minh');
    return (new \DateTime('now', $tz))->format('Ymd'); // e.g. 20250821
  }
  private function getHmacAuthHeader(): string
  {
    $clientKeySource = $this->clientId; // bám theo Postman: CLIENT_KEY = env('clientId')
    $today            = $this->todayYmd();
    $sourceData       = $clientKeySource . $today . $this->secretKey;
    $rawHmac = hash_hmac('sha512', $sourceData, $this->secretKey, true); // raw binary
    return base64_encode($rawHmac);
  }

  private function getClientKeyBase64(): string
  {
    return base64_encode($this->clientId);
  }

  private function json_minify(array $data): string
  {
    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  }

  public function sign($json_payload)
  {
    $pkeyid = openssl_pkey_get_private($this->getPrivateKey());
    if (!$pkeyid) {
      throw new \RuntimeException('Invalid private key');
    }
    openssl_sign($json_payload, $signature, $pkeyid, OPENSSL_ALGO_SHA256);
    return base64_encode($signature);
  }

  /**
   * Verify chữ ký (RSA-SHA256)
   */
  public function verifySign($bodyMinified, string $signature): bool
  {
    $pub = openssl_pkey_get_public($this->getPublicKey());
    if (!$pub) {
      throw new \RuntimeException('Invalid public key');
    }

    $ok = openssl_verify($bodyMinified, base64_decode($signature), $pub, OPENSSL_ALGO_SHA256) === 1;
    openssl_free_key($pub);
    return $ok;
  }

  /**
   * Mã hoá bằng public key (RSA-OAEP)
   */
  private function encode(string $data): string
  {
    $pub = openssl_pkey_get_public($this->getPublicKey());
    if (!$pub) {
      throw new \RuntimeException('Invalid public key');
    }

    if (!openssl_public_encrypt($data, $ciphertext, $pub, OPENSSL_PKCS1_OAEP_PADDING)) {
      openssl_free_key($pub);
      throw new \RuntimeException('Encrypt failed: ' . openssl_error_string());
    }
    openssl_free_key($pub);

    return base64_encode($ciphertext);
  }

  /**
   * Giải mã bằng private key (RSA-OAEP)
   */
  private function decode(string $base64Cipher): string
  {
    $priv = openssl_pkey_get_private($this->getPrivateKey());
    if (!$priv) {
      throw new \RuntimeException('Invalid private key');
    }

    $cipher = base64_decode($base64Cipher, true);
    if ($cipher === false) {
      throw new \InvalidArgumentException('Cipher must be base64');
    }

    if (!openssl_private_decrypt($cipher, $plain, $priv, OPENSSL_PKCS1_OAEP_PADDING)) {
      openssl_free_key($priv);
      throw new \RuntimeException('Decrypt failed: ' . openssl_error_string());
    }
    openssl_free_key($priv);

    return $plain;
  }
  private function getPrivateKey()
  {
    $b64 = preg_replace('/\s+/', '', $this->privateKey);
    return "-----BEGIN PRIVATE KEY-----\n" . chunk_split($b64, 64, "\n") . "-----END PRIVATE KEY-----\n";
  }
  private function getPublicKey()
  {
    $b64 = preg_replace('/\s+/', '', $this->publicKey);
    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split($b64, 64, "\n") . "-----END PUBLIC KEY-----\n";
  }
}
