<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTopping;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
  /**
   * Tạo hóa đơn từ đơn hàng
   */
  public function createInvoiceFromOrder($orderId,  $paidAmount = 0)
  {
    return DB::transaction(function () use ($orderId,  $paidAmount) {
      $order = Order::findOrFail($orderId);

      if ($order->order_status !== 'completed') {
        throw new \Exception("Đơn hàng chưa hoàn tất, không thể tạo hóa đơn.");
      }

      // Tạo hóa đơn
      $invoice = Invoice::create([
        'order_id' => $order->id,
        'customer_id' => $order->customer_id,
        'branch_id' => $order->branch_id,
        'discount_amount' => $order->discount_amount,
        'paid_amount' => $paidAmount,
        'invoice_status' => $paidAmount > 0 ? ($paidAmount >= $order->total_price ? 'completed' : 'pending') : 'pending',
        'payment_status' => $paidAmount > 0 ? ($paidAmount >= $order->total_price ? 'paid' : 'partial') : 'unpaid',
        'note' => $order->note,
      ]);

      //Tải items và toppings
      $order->loadMissing(['items', 'items.toppings']);

      // Sao chép sản phẩm từ đơn hàng
      $this->copyOrderItemsToInvoice($order, $invoice);

      $invoice->refresh();

      // Cập nhật tổng tiền hóa đơn
      $this->updateInvoiceTotal($invoice);

      return $invoice;
    });
  }

  private function copyOrderItemsToInvoice(Order $order, Invoice $invoice)
  {
    foreach ($order->items as $orderItem) {
      $invoiceItem = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'product_id' => $orderItem->product_id,
        'quantity' => $orderItem->quantity,
        'unit_price' => $orderItem->unit_price,
        'total_price' => $orderItem->unit_price * $orderItem->quantity,
      ]);

      $this->copyOrderToppingsToInvoice($orderItem, $invoiceItem);
    }
  }

  private function copyOrderToppingsToInvoice(OrderItem $orderItem, InvoiceItem $invoiceItem)
  {
    foreach ($orderItem->toppings as $orderTopping) {
      InvoiceTopping::create([
        'invoice_item_id' => $invoiceItem->id,
        'topping_id' => $orderTopping->topping_id,
        'unit_price' => $orderTopping->unit_price,
      ]);
    }
  }

  /**
   * Cập nhật tổng tiền hóa đơn
   */
  private function updateInvoiceTotal(Invoice $invoice)
  {
    $total = 0;
    foreach ($invoice->items as $invoiceItem) {
      // Tổng tiền sản phẩm
      $productTotal = $invoiceItem->unit_price * $invoiceItem->quantity;

      // Tổng tiền topping
      $toppingTotal = $invoiceItem->toppings?->sum(fn($t) => $t->unit_price * $invoiceItem->quantity) ?? 0;

      $total += ($productTotal + $toppingTotal);
      $invoiceItem->total_price_with_topping = ($productTotal + $toppingTotal);
      $invoiceItem->save();
    }

    // Trừ giảm giá nếu có
    $invoice->total_amount = max($total - $invoice->discount_amount, 0);
    $invoice->save();
  }


  /**
   * Cập nhật trạng thái thanh toán của hóa đơn
   */
  public function updatePaymentStatus($invoiceId, $status)
  {
    return DB::transaction(function () use ($invoiceId, $status) {
      $invoice = Invoice::findOrFail($invoiceId);

      if (!in_array($status, ['unpaid', 'partial', 'paid', 'refunded'])) {
        throw new \Exception("Trạng thái thanh toán không hợp lệ.");
      }

      $invoice->payment_status = $status;
      $invoice->save();

      // Nếu hóa đơn được thanh toán đầy đủ, tự động đánh dấu là hoàn tất
      if ($invoice->isPaid()) {
        $invoice->markAsCompleted();
      }

      return $invoice;
    });
  }



  /**
   * Tìm kiếm hóa đơn theo mã
   */
  public function findInvoiceByCode($code)
  {
    return Invoice::where('code', strtoupper($code))->first();
  }

  /**
   * Lấy danh sách hóa đơn (phân trang)
   */
  public function getInvoices($perPage = 10)
  {
    return Invoice::orderBy('created_at', 'desc')->paginate($perPage);
  }
}
