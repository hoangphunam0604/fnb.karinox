<?php

namespace App\Listeners;

use App\Events\OrderCompleted;
use App\Services\InvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateInvoiceListener /* implements ShouldQueue */
{
  protected InvoiceService $invoiceService;

  public function __construct(InvoiceService $invoiceService)
  {
    $this->invoiceService = $invoiceService;
  }

  public function handle(OrderCompleted $event)
  {
    $order = $event->order;
    $print = $event->print;

    // Gọi InvoiceService để tạo hóa đơn cho đơn hàng
    $this->invoiceService->createInvoiceFromOrder($order->id, $print);
    //Cập nhật tồn 
  }
}
