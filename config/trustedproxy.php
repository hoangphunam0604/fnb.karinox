<?php

use Illuminate\Http\Request;

return [

  /*
    |--------------------------------------------------------------------------
    | Trusted Proxy IP Addresses
    |--------------------------------------------------------------------------
    |
    | Use '*' to trust all proxies, or define an array of trusted proxy IPs.
    |
    */
  'proxies' => '*',

  /*
    |--------------------------------------------------------------------------
    | Headers that should be used to detect proxies
    |--------------------------------------------------------------------------
    |
    | These headers define how trusted proxies communicate with your app.
    | For full support behind load balancers (like Cloudflare), use:
    |
    | - HEADER_X_FORWARDED_ALL
    |
    */
  'headers' =>
  Request::HEADER_X_FORWARDED_FOR |
    Request::HEADER_X_FORWARDED_HOST |
    Request::HEADER_X_FORWARDED_PORT |
    Request::HEADER_X_FORWARDED_PROTO,
];
