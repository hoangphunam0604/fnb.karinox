<?php

namespace App\Services\PaymentGateways;

class CashGateway extends BaseGateway
{

  protected function getPaymentMethod(): string
  {
    return 'cash';
  }
}
