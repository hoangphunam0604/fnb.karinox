<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTopping;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTopping;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
  /**
   * Tạo hóa đơn từ đơn hàng
   */
  public function createInvoiceFromOrder($orderId)
  {
    return DB::transaction(function () use ($orderId) {
      $order = Order::findOrFail($orderId);

      if ($order->status !== 'completed') {
        throw new \Exception("Đơn hàng chưa hoàn tất, không thể tạo hóa đơn.");
      }

      // Tạo hóa đơn
      $invoice = Invoice::create([
        'code' => strtoupper(uniqid('INV')),
        'order_id' => $order->id,
        'customer_id' => $order->customer_id,
        'branch_id' => $order->branch_id,
        'total_price' => 0, // Sẽ được cập nhật sau
        'discount_amount' => $order->discount_amount,
        'status' => 'unpaid',
        'note' => $order->note,
      ]);

      // Sao chép sản phẩm từ đơn hàng
      $this->copyOrderItemsToInvoice($order, $invoice);

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
      $toppingTotal = $invoiceItem->toppings->sum(fn($t) => $t->unit_price * $invoiceItem->quantity);

      $total += ($productTotal + $toppingTotal);
    }

    // Trừ giảm giá nếu có
    $invoice->total_price = max($total - $invoice->discount_amount, 0);
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
