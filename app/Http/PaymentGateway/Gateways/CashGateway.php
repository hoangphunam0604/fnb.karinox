<?php

namespace App\Http\PaymentGateway\Gateways;

class CashGateway extends BaseGateway
{

  protected function getPaymentMethod(): string
  {
    return 'cash';
  }
}
