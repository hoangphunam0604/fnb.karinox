<?php

return [
  'endpoint' => env('VNPAYQR_ENDPOINT'),
  'app_id' => env('VNPAYQR_APP_ID'),
  'merchant_name' => env('VNPAYQR_MERCHANT_NAME'),
  'merchant_code' => env('VNPAYQR_MERCHANT_CODE'),
  'terminal_id' => env('VNPAYQR_TERMINAL_ID'),
  'master_mer_code' => env('VNPAYQR_MASTER_MER_CODE'),
  'merchant_type' => env('VNPAYQR_MERCHANT_TYPE'),
  'service_code' => env('VNPAYQR_SERVICE_CODE'),
  'secret_key_gen' => env('VNPAYQR_SECRET_KEY_GEN'),
  'secret_key_check' => env('VNPAYQR_SECRET_KEY_CHECK'),
  'secret_key_refurn' => env('VNPAYQR_SECRET_KEY_REFUND'),
  'secret_key_ipn' => env('VNPAYQR_SECRET_KEY_IPN'),
];
