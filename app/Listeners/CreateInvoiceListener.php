<?php

namespace App\Listeners;

use App\Events\OrderCompleted;
use App\Services\InvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateInvoiceListener implements ShouldQueue
{
  protected InvoiceService $invoiceService;

  public function __construct(InvoiceService $invoiceService)
  {
    $this->invoiceService = $invoiceService;
  }

  public function handle(OrderCompleted $event)
  {
    $order = $event->order;

    // Gọi InvoiceService để tạo hóa đơn cho đơn hàng
    $this->invoiceService->createInvoiceFromOrder($order->id);
    //Cập nhật tồn 
  }
}
