<?php

return [
  'base_url'  =>  env('INFOPLUS_BASE_URL', ''),

  'userId'  =>  env('INFOPLUS_USER_ID', ''),
  'clientId'  =>  env('INFOPLUS_CLIENT_ID', ''),
  'clientKey' =>  env('INFOPLUS_CLIENT_KEY', ''),
  'secretKey' =>  env('INFOPLUS_SECRET_KEY', ''),
  'privateKey' =>  env('INFOPLUS_PRIVATE_KEY', ''),
  'publicKey'  =>  env('INFOPLUS_PUBLIC_KEY', ''),
  'bankCode'  =>  env('INFOPLUS_BANK_CODE', ''),

  'posUniqueId' =>  env('INFOPLUS_POS_UNIQUE_ID'),
  'posFranchiseeName' =>  env('INFOPLUS_POS_FRANCHISEE_NAME'),
  'posCompanyName'  =>  env('INFOPLUS_POS_COMPANY_NAME'),

];
