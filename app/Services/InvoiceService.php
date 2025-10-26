<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\InvoiceCreated;
use App\Events\PrintRequested;
use App\Models\InvoiceTopping;
use App\Models\InvoiceItem;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Order;
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
  public function findById(int $id): ?Invoice
  {
    return Invoice::find($id);
  }
  public function createInvoiceFromOrder(int $orderId, bool $requestPrint = false): Invoice
  {

    return DB::transaction(function () use ($orderId, $requestPrint) {
      $order = Order::findOrFail($orderId);

      if ($order->order_status !== OrderStatus::COMPLETED) {
        throw new \Exception("Đơn hàng chưa hoàn tất, không thể tạo hóa đơn.");
      }

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
        'user_id' => $order->user_id, // Nhân viên bán hàng
        'table_name' => $order->table?->name, // Lưu tên bàn

        'subtotal_price' => $order->subtotal_price,
        'discount_amount' => $order->discount_amount,
        'reward_points_used' => $order->reward_points_used,
        'reward_discount' => $order->reward_discount,
        'total_price' => $order->total_price,

        'tax_rate' => $taxData['tax_rate'],
        'tax_amount' => $taxData['tax_amount'],
        'total_price_without_vat' => $taxData['total_price_without_vat'],

        'paid_amount' => $totalPrice,
        'invoice_status' => InvoiceStatus::COMPLETED,
        'payment_status' => PaymentStatus::PAID,
        'payment_method' => $order->payment_method,
        'note' => $order->note,
      ]);
      $this->voucherService->transferUsedPointsToInvoice($order->id, $invoice->id);
      $this->pointService->earnPointsOnTransactionCompletion($invoice);

      $order->loadMissing(['items', 'items.toppings']);
      $this->copyOrderItemsToInvoice($order, $invoice);

      // Fire InvoiceCreated event after all data is saved
      event(new InvoiceCreated($invoice));

      // Nếu có yêu cầu in (print = true), broadcast PrintRequested
      if ($requestPrint) {
        $this->requestPrint($invoice->id);
      }

      return $invoice;
    });
  }

  /**
   * Đánh dấu hóa đơn đã được yêu cầu in
   */
  public function requestPrint(int $invoiceId): Invoice
  {
    $invoice = Invoice::findOrFail($invoiceId);
    if ($invoice->payment_status !== PaymentStatus::PAID)
      throw new \Exception("Đơn hàng chưa được thanh toán");

    $invoice->increment('print_requested_count');
    $invoice->update(['print_requested_at' => now()]);
    broadcast(new PrintRequested('invoice-all', ['id' => $invoice->id], $invoice->branch_id));
    return $invoice;
  }

  /**
   * Đánh dấu hóa đơn đã được in
   */
  public function markAsPrinted(int $invoiceId): Invoice
  {
    $invoice = Invoice::findOrFail($invoiceId);
    $invoice->increment('print_count');
    $invoice->update(['last_printed_at' => now()]);
    return $invoice;
  }

  /**
   * Hoàn tiền cho hóa đơn
   */
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

  public function getPrintRequestedInvoices($branchId): \Illuminate\Pagination\LengthAwarePaginator
  {
    return Invoice::printRequested()
      ->where('branch_id', $branchId)
      ->orderBy('print_requested_at', 'desc')
      ->select(['id', 'code', 'customer_name', 'total_price', 'print_requested_at', 'print_count', 'last_printed_at'])
      ->paginate(50);
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
}
