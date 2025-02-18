<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\InvoiceTopping;
use App\Models\InvoiceItem;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
  public function findInvoiceByCode(string $code): ?Invoice
  {
    return Invoice::where('code', strtoupper($code))->first();
  }

  public function getInvoices(int $perPage = 10): LengthAwarePaginator
  {
    return Invoice::orderBy('created_at', 'desc')->paginate($perPage);
  }


  public function createInvoiceFromOrder(int $orderId, float $paidAmount = 0): Invoice
  {
    return DB::transaction(function () use ($orderId, $paidAmount) {
      $order = Order::findOrFail($orderId);

      if ($order->order_status !== 'completed') {
        throw new \Exception("Đơn hàng chưa hoàn tất, không thể tạo hóa đơn.");
      }

      $invoice = Invoice::create([
        'order_id' => $order->id,
        'customer_id' => $order->customer_id,
        'branch_id' => $order->branch_id,
        'discount_amount' => $order->discount_amount,
        'paid_amount' => $paidAmount,
        'invoice_status' => 'pending',
        'payment_status' => 'unpaid',
        'note' => $order->note,
      ]);

      $order->loadMissing(['items', 'items.toppings']);
      $this->copyOrderItemsToInvoice($order, $invoice);
      $invoice->refresh();
      $this->updateInvoiceTotal($invoice);

      return $invoice;
    });
  }

  private function copyOrderItemsToInvoice(Order $order, Invoice $invoice): void
  {
    foreach ($order->items as $orderItem) {
      $invoiceItem = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'product_id' => $orderItem->product_id,
        'quantity' => $orderItem->quantity,
        'unit_price' => $orderItem->unit_price,
        'total_price' => $orderItem->total_price,
        'total_price_with_topping' => $orderItem->total_price_with_topping,
      ]);
      $this->copyOrderToppingsToInvoice($orderItem, $invoiceItem);
    }
  }

  private function copyOrderToppingsToInvoice(OrderItem $orderItem, InvoiceItem $invoiceItem): void
  {
    foreach ($orderItem->toppings as $orderTopping) {
      InvoiceTopping::create([
        'invoice_item_id' => $invoiceItem->id,
        'topping_id' => $orderTopping->topping_id,
        'quantity' => $orderTopping->quantity,
        'unit_price' => $orderTopping->unit_price,
        'total_price' => $orderTopping->total_price,
      ]);
    }
  }

  private function updateInvoiceTotal(Invoice $invoice): void
  {
    $total = $invoice->items->sum(fn($item) => $item->total_price_with_topping);
    $invoice->total_amount = max($total - $invoice->discount_amount, 0);
    $invoice->save();
  }

  public function updatePaymentMethod(int $invoiceId, string $method): Invoice
  {
    $invoice = Invoice::findOrFail($invoiceId);
    $invoice->payment_method = $method;
    $invoice->save();
    return $invoice;
  }

  public function updatePaymentStatus(int $invoiceId, string $status): Invoice
  {
    return DB::transaction(function () use ($invoiceId, $status) {
      $invoice = Invoice::findOrFail($invoiceId);
      if (!in_array($status, ['unpaid', 'partial', 'paid', 'refunded'])) {
        throw new \Exception("Trạng thái thanh toán không hợp lệ.");
      }
      $invoice->payment_status = $status;
      $invoice->save();

      if ($invoice->isPaid()) {
        $invoice->markAsCompleted();
      }
      return $invoice;
    });
  }

  public function canBeRefunded(Invoice $invoice): bool
  {
    return (bool) $invoice->customer;
  }
}
