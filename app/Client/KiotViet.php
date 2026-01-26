<?php

namespace App\Client;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class KiotViet
{

  protected $client;
  protected $kiotviet_connect_url = 'https://id.kiotviet.vn/connect/token';
  protected $kiotviet_connect_scopes = 'PublicApi.Access.FNB';
  protected $kiotviet_connect_grant_type = 'client_credentials';
  protected $kiotviet_connect_client_id = 'd6b2f125-3146-4ae4-8c61-1548ec4ebda5';
  protected $kiotviet_connect_client_secret = '91CB9B412A00AF744CB345B5062F984A8333C2ED';
  protected $kioviet_access_token;

  protected $api = 'https://publicfnb.kiotapi.com/';

  public function __construct()
  {
    $this->client   = new Client();
    $this->kioviet_access_token = $this->getAccessToken();
  }

  public function createOrder($data)
  {
    return $this->post('orders/create', $data);
  }

  public function getBrands($pageSize  = 100, $currentItem =  0)
  {
    return $this->get('branches', [
      'pageSize' => $pageSize,
      'currentItem' => $currentItem,
      'includePricebook' => 1
    ]);
  }

  public function getProducts($pageSize  = 100, $currentItem =  0)
  {
    return $this->get('products', [
      'pageSize' => $pageSize,
      'currentItem' => $currentItem,
      'includePricebook' => 1
    ]);
  }

  public function getCustomers($pageSize  = 100, $currentItem =  0)
  {
    return $this->get('customers', [
      'pageSize' => $pageSize,
      'currentItem' => $currentItem,
      'includeTotal' => 1
    ]);
  }
  protected function getAccessToken()
  {
    return Cache::remember("kiotviet_access_token", 1200, function () {
      $response = $this->client->post($this->kiotviet_connect_url, [
        'form_params'  =>  [
          'scopes'  =>  $this->kiotviet_connect_scopes,
          'grant_type'  =>  $this->kiotviet_connect_grant_type,
          'client_id' =>  $this->kiotviet_connect_client_id,
          'client_secret' =>  $this->kiotviet_connect_client_secret
        ]
      ]);
      $data = $this->getContents($response);
      return $data["access_token"];
    });
  }

  public function post($endpoint, $data)
  {
    $response = $this->client->post($this->api . $endpoint, [
      'headers' => [
        'Retailer' => 'karinopr',
        'Authorization'     => "Bearer {$this->kioviet_access_token}"
      ],
      'form_params' => $data
    ]);
    return $this->getContents($response);
  }

  public function get($endpoint, $query = [])
  {
    $response = $this->client->get($this->api . $endpoint, [
      'headers' => [
        'Retailer' => 'karinopr',
        'Authorization'     => "Bearer {$this->kioviet_access_token}"
      ],
      'query' => $query
    ]);
    return json_decode($response->getBody()->getContents(), true);
  }

  protected function getContents($response)
  {
    return json_decode($response->getBody()->getContents(), true);
  }
}
