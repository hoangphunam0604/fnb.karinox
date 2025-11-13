<?php

namespace App\Services\PaymentGateways;

class CardGateway extends BaseGateway
{

  protected function getPaymentMethod(): string
  {
    return 'card';
  }
}
