<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use App\Services\OrderService;

abstract class BaseGateway
{
  public function __construct() {}

  abstract protected function getPaymentMethod(): string;

  /**
   * Xử lý thanh toán
   * Gateway chỉ validate và delegate việc update order cho OrderService
   */
  public function pay(Order $order): bool
  {
    // Validate payment requirements (có thể override trong subclass)
    if (!$this->validatePayment($order)) {
      return false;
    }

    // Delegate việc update order và fire events cho OrderService
    $orderService = app(OrderService::class);
    return $orderService->completePayment($order, $this->getPaymentMethod(), true);
  }

  /**
   * Validate payment requirements (có thể override trong subclass)
   */
  protected function validatePayment(Order $order): bool
  {
    // Kiểm tra cơ bản
    if (!$order || $order->total_price <= 0) {
      return false;
    }

    return true;
  }
}
