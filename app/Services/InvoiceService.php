<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\InvoiceTopping;
use App\Models\InvoiceItem;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
  protected TaxService $taxService;
  protected PointService $pointService;

  public function __construct(TaxService $taxService, PointService $pointService)
  {
    $this->taxService = $taxService;
    $this->pointService = $pointService;
  }
  public function findInvoiceByCode(string $code): ?Invoice
  {
    return Invoice::where('code', strtoupper($code))->first();
  }

  public function getInvoices(int $perPage = 10): LengthAwarePaginator
  {
    return Invoice::with(['customer', 'order'])
      ->orderBy('created_at', 'desc')
      ->paginate($perPage);
  }


  public function createInvoiceFromOrder(int $orderId, float $paidAmount = 0): Invoice
  {
    return DB::transaction(function () use ($orderId, $paidAmount) {
      $order = Order::findOrFail($orderId);

      if ($order->order_status !== OrderStatus::COMPLETED) {
        throw new \Exception("Đơn hàng chưa hoàn tất, không thể tạo hóa đơn.");
      }
      if (!$paidAmount)
        $paidAmount = $order->total_price;

      $invoice = Invoice::create([
        'order_id' => $order->id,
        'customer_id' => $order->customer_id,
        'branch_id' => $order->branch_id,

        'subtotal_price' => $order->subtotal_price,
        'discount_amount' => $order->discount_amount,
        'reward_points_used' => $order->reward_points_used,
        'reward_discount' => $order->reward_discount,

        'paid_amount' => $paidAmount,
        'invoice_status' => 'pending',
        'payment_status' => 'unpaid',
        'note' => $order->note,
      ]);

      $order->loadMissing(['items', 'items.toppings']);
      $this->copyOrderItemsToInvoice($order, $invoice);
      $invoice->refresh();
      $this->updateInvoiceTotal($invoice);
      $this->pointService->earnPointsOnTransactionCompletion($invoice);

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
    $totalPrice = max($total - $invoice->discount_amount - $invoice->reward_discount, 0);

    // Tính toán thuế bằng TaxService
    $taxData = $this->taxService->calculateTax($totalPrice) ?: [
      'tax_rate' => null,
      'tax_amount' => 0,
      'total_price_without_vat' => $totalPrice
    ];

    $invoice->total_price = $totalPrice;
    $invoice->tax_rate = $taxData['tax_rate'];
    $invoice->tax_amount = $taxData['tax_amount'];
    $invoice->total_price_without_vat = $taxData['total_price_without_vat'];
    $invoice->save();
  }

  public function updatePaymentMethod(int $invoiceId, string $method): Invoice
  {
    $invoice = Invoice::findOrFail($invoiceId);
    $invoice->payment_method = $method;
    $invoice->save();
    return $invoice;
  }

  public function updatePaymentStatus(int $invoiceId, PaymentStatus $status): Invoice
  {
    return DB::transaction(function () use ($invoiceId, $status) {
      $invoice = Invoice::findOrFail($invoiceId);
      $invoice->payment_status = $status;
      $invoice->save();

      if ($invoice->isPaid() && $invoice->invoice_status !== InvoiceStatus::CANCELED) {
        $invoice->markAsCompleted();
      }
      return $invoice;
    });
  }

  public function canBeRefunded(Invoice $invoice): bool
  {
    return $invoice->customer && $invoice->payment_status === PaymentStatus::PAID;
  }
}
