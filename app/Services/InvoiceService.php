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
use Faker\Provider\ar_EG\Payment;
use Illuminate\Support\Facades\DB;

class InvoiceService extends BaseService
{
  protected TaxService $taxService;
  protected PointService $pointService;
  protected VoucherService $voucherService;
  protected StockDeductionService $stockDeductionService;

  public function __construct(
    TaxService $taxService,
    PointService $pointService,
    VoucherService $voucherService,
    StockDeductionService $stockDeductionService
  ) {
    $this->taxService = $taxService;
    $this->pointService = $pointService;
    $this->voucherService = $voucherService;
    $this->stockDeductionService = $stockDeductionService;
  }

  protected function model(): Invoice
  {
    return new Invoice();
  }

  public function findByCode(string $code): ?Invoice
  {
    return Invoice::where('code', strtoupper($code))->first();
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

      $totalPrice = $order->total_price;
      // Tính toán thuế bằng TaxService
      $taxData = $this->taxService->calculateTax($totalPrice) ?: [
        'tax_rate' => null,
        'tax_amount' => 0,
        'total_price_without_vat' => $totalPrice
      ];



      $invoice = Invoice::create([
        'order_id' => $order->id,
        'customer_id' => $order->customer_id,
        'branch_id' => $order->branch_id,

        'subtotal_price' => $order->subtotal_price,
        'discount_amount' => $order->discount_amount,
        'reward_points_used' => $order->reward_points_used,
        'reward_discount' => $order->reward_discount,
        'total_price' => $order->total_price,

        'tax_rate' => $taxData['tax_rate'],
        'tax_amount' => $taxData['tax_amount'],
        'total_price_without_vat' => $taxData['total_price_without_vat'],

        'paid_amount' => $paidAmount,
        'invoice_status' => InvoiceStatus::COMPLETED,
        'payment_status' => $paidAmount == $order->total_price ? PaymentStatus::PAID : PaymentStatus::UNPAID,
        'payment_method' => $order->payment_method,
        'note' => $order->note,
      ]);
      $this->voucherService->transferUsedPointsToInvoice($order->id, $invoice->id);
      $this->pointService->earnPointsOnTransactionCompletion($invoice);

      $order->loadMissing(['items', 'items.toppings']);
      $this->copyOrderItemsToInvoice($order, $invoice);
      return $invoice;
    });
  }

  private function copyOrderItemsToInvoice(Order $order, Invoice $invoice): void
  {
    foreach ($order->items as $orderItem) {
      $invoiceItem = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'product_id' => $orderItem->product_id,
        'product_name' => $orderItem->product_name,
        'product_price' => $orderItem->product_price,
        'quantity' => $orderItem->quantity,
        'unit_price' => $orderItem->unit_price,
        'total_price' => $orderItem->total_price,
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
        'topping_name' => $orderTopping->topping_name,
        'quantity' => $orderTopping->quantity,
        'unit_price' => $orderTopping->unit_price,
        'total_price' => $orderTopping->total_price,
      ]);
    }
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

  public function refunded(int $invoiceId): Invoice
  {
    return DB::transaction(function () use ($invoiceId) {
      $invoice = Invoice::findOrFail($invoiceId);
      if (!$invoice->canBeRefunded())
        throw new \Exception("Đơn hàng chưa thanh toán hoặc được giảm giá, không thể hoàn tiền.");

      $invoice->payment_status = PaymentStatus::REFUNDED;
      $invoice->invoice_status = InvoiceStatus::CANCELED;
      $invoice->save();
      // Hoàn trả tồn kho
      $this->stockDeductionService->restoreStockForRefundedInvoice($invoice);
      return $invoice;
    });
  }
}
