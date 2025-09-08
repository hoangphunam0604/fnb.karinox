<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use App\Enums\PaymentStatus;
use App\Enums\OrderStatus;
use App\Events\OrderCompleted;
use Carbon\Carbon;

abstract class BaseGateway
{
  public function __construct() {}

  abstract  protected function getPaymentMethod(): string;

  /**
   * Thanh toán
   * 
   */
  public function pay(Order $order)
  {
    if ($order->order_status == OrderStatus::COMPLETED)
      return true;

    return DB::transaction(function () use ($order) {
      $order->update([
        'paid_at' =>  Carbon::now(),
        'payment_method'  =>  $this->getPaymentMethod(),
        'payment_status'  =>  PaymentStatus::PAID,
        'order_status' => OrderStatus::COMPLETED
      ]);
      event(new OrderCompleted($order));
      return true;
    });
  }
}
